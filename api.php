<?php
session_start();

const CLIENT_NAMES = [
    'Технопарк Север',
    'IT-кластер «Вектор»',
    'Научный центр «Орбита»',
    'Мобильная сеть «Связь+»',
    'Аэропорт «Полюс»',
    'Портовый узел «Северный»'
];

function baseState(): array {
    $satellites = [
        [
            'id' => 'orbita-12',
            'name' => 'Орбита-12',
            'longitude' => 36.0,
            'azimuth' => 192,
            'elevation' => 41,
            'bandwidth' => 180
        ],
        [
            'id' => 'altair-7',
            'name' => 'Альтаир-7',
            'longitude' => 56.5,
            'azimuth' => 160,
            'elevation' => 37,
            'bandwidth' => 220
        ],
        [
            'id' => 'signal-5',
            'name' => 'Сигнал-5',
            'longitude' => 13.0,
            'azimuth' => 213,
            'elevation' => 43,
            'bandwidth' => 150
        ],
        [
            'id' => 'aurora-net',
            'name' => 'Аврора-Net',
            'longitude' => 82.0,
            'azimuth' => 145,
            'elevation' => 33,
            'bandwidth' => 260
        ],
        [
            'id' => 'meridian-pro',
            'name' => 'Меридиан-Pro',
            'longitude' => -5.0,
            'azimuth' => 234,
            'elevation' => 39,
            'bandwidth' => 190
        ]
    ];

    $clients = [];
    $id = 1;
    foreach (CLIENT_NAMES as $name) {
        $clients[] = [
            'id' => $id,
            'name' => $name,
            'demand' => rand(40, 120),
            'buffer' => rand(10, 40),
            'status' => 'Ожидает'
        ];
        $id++;
    }

    $eventLog = [[
        'timestamp' => date('H:i:s'),
        'message' => 'Станция в режиме ожидания. Выберите спутник для связи.'
    ]];

    return [
        'orientation' => [
            'azimuth' => 180,
            'elevation' => 45
        ],
        'targetSatellite' => null,
        'satellites' => $satellites,
        'clients' => $clients,
        'lastUpdate' => microtime(true),
        'eventLog' => $eventLog,
        'linkLocked' => false
    ];
}

if (!isset($_SESSION['sim_state'])) {
    $_SESSION['sim_state'] = baseState();
}

$state = $_SESSION['sim_state'];

$action = $_GET['action'] ?? 'status';

function response(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function logEvent(array &$state, string $message): void {
    $state['eventLog'][] = [
        'timestamp' => date('H:i:s'),
        'message' => $message
    ];
    if (count($state['eventLog']) > 25) {
        $state['eventLog'] = array_slice($state['eventLog'], -25);
    }
}

function calculateSignal(array $orientation, array $satellite): array {
    $azDiff = abs($orientation['azimuth'] - $satellite['azimuth']);
    $azDiff = min($azDiff, 360 - $azDiff);
    $elDiff = abs($orientation['elevation'] - $satellite['elevation']);

    $distance = sqrt(pow($azDiff / 2, 2) + pow($elDiff * 1.2, 2));
    $quality = max(0, 100 - $distance * 5);
    $speed = round(($quality / 100) * $satellite['bandwidth']);

    return [
        'quality' => round($quality),
        'speed' => max(0, $speed)
    ];
}

function distributeTraffic(array &$state, array $signal, ?string $targetId): void {
    foreach ($state['clients'] as &$client) {
        $client['status'] = 'Ожидает';
    }

    $wasLocked = $state['linkLocked'] ?? false;
    $isLocked = $targetId && $signal['quality'] >= 70;

    if ($isLocked && !$wasLocked) {
        logEvent($state, 'Канал стабилизирован. Передача активна.');
    }

    if (!$isLocked && $wasLocked) {
        logEvent($state, 'Потеря связи со спутником. Качество сигнала упало.');
    }

    $state['linkLocked'] = $isLocked;

    if (!$targetId || $signal['quality'] < 40) {
        foreach ($state['clients'] as &$client) {
            $client['buffer'] = max(0, round($client['buffer'] - 1.5, 1));
        }
        return;
    }

    $throughput = $signal['speed'];
    $clientsCount = count($state['clients']);
    if ($clientsCount === 0) {
        return;
    }

    $perClient = $throughput / $clientsCount;
    foreach ($state['clients'] as &$client) {
        $client['buffer'] += round($perClient / 8, 1);
        if ($client['buffer'] > $client['demand']) {
            $client['buffer'] = $client['demand'];
            $client['status'] = 'Получил данные';
        } else {
            $client['status'] = 'Передача...';
        }
    }
}

function findSatellite(array $state, string $id): ?array {
    foreach ($state['satellites'] as $sat) {
        if ($sat['id'] === $id) {
            return $sat;
        }
    }
    return null;
}

switch ($action) {
    case 'status':
        $targetSat = $state['targetSatellite'] ? findSatellite($state, $state['targetSatellite']) : null;
        $signal = $targetSat ? calculateSignal($state['orientation'], $targetSat) : ['quality' => 0, 'speed' => 0];
        distributeTraffic($state, $signal, $state['targetSatellite']);
        $_SESSION['sim_state'] = $state;
        response([
            'orientation' => $state['orientation'],
            'targetSatellite' => $state['targetSatellite'],
            'satellites' => $state['satellites'],
            'clients' => $state['clients'],
            'signal' => $signal,
            'linkLocked' => $state['linkLocked'],
            'events' => $state['eventLog']
        ]);
    case 'set-orientation':
        $az = isset($_POST['azimuth']) ? (float) $_POST['azimuth'] : $state['orientation']['azimuth'];
        $el = isset($_POST['elevation']) ? (float) $_POST['elevation'] : $state['orientation']['elevation'];
        $state['orientation'] = [
            'azimuth' => max(0, min(360, $az)),
            'elevation' => max(0, min(90, $el))
        ];
        $targetSat = $state['targetSatellite'] ? findSatellite($state, $state['targetSatellite']) : null;
        if ($targetSat) {
            $signal = calculateSignal($state['orientation'], $targetSat);
            distributeTraffic($state, $signal, $state['targetSatellite']);
            if ($signal['quality'] >= 85) {
                logEvent($state, 'Антенна точно наведена на ' . $targetSat['name'] . '.');
            }
        }
        $_SESSION['sim_state'] = $state;
        response(['orientation' => $state['orientation']]);
    case 'set-target':
        $targetId = $_POST['satellite'] ?? null;
        if ($targetId && findSatellite($state, $targetId)) {
            $state['targetSatellite'] = $targetId;
            $sat = findSatellite($state, $targetId);
            logEvent($state, 'Выбран спутник ' . $sat['name'] . '. Наводите антенну.');
        }
        $_SESSION['sim_state'] = $state;
        response(['targetSatellite' => $state['targetSatellite']]);
    case 'reset':
        $_SESSION['sim_state'] = baseState();
        response(['reset' => true]);
    default:
        response(['error' => 'Неизвестное действие']);
}
