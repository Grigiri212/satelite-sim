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

function environmentDefaults(): array {
    return [
        'weather' => 'Ясно',
        'solarActivity' => 'Низкая',
        'interference' => 6,
        'latency' => 520,
        'packetLoss' => 0.4,
        'powerLoad' => 34,
        'temperature' => -5,
        'lastUpdate' => microtime(true)
    ];
}

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
        'linkLocked' => false,
        'environment' => environmentDefaults()
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

function applyEnvironmentEffects(array $signal, array $environment): array {
    $quality = $signal['quality'];
    $speed = $signal['speed'];

    $qualityModifier = 1.0;
    switch ($environment['weather']) {
        case 'Шторм':
            $qualityModifier -= 0.38;
            break;
        case 'Облачно':
            $qualityModifier -= 0.14;
            break;
        case 'Снег':
            $qualityModifier -= 0.22;
            break;
    }

    switch ($environment['solarActivity']) {
        case 'Высокая':
            $qualityModifier -= 0.18;
            break;
        case 'Повышенная':
            $qualityModifier -= 0.08;
            break;
    }

    $quality = max(0, min(100, round(max(0.1, $quality) * $qualityModifier - $environment['interference'] * 0.6)));
    if ($signal['quality'] > 0) {
        $speed = max(0, round($signal['speed'] * ($quality / $signal['quality'])));
    } else {
        $speed = 0;
    }

    if ($environment['weather'] === 'Шторм') {
        $speed = (int) round($speed * 0.8);
    }

    return [
        'quality' => $quality,
        'speed' => $speed
    ];
}

function updateEnvironment(array &$state): void {
    if (!isset($state['environment'])) {
        $state['environment'] = environmentDefaults();
    }

    $env = $state['environment'];
    $now = microtime(true);
    if (($now - ($env['lastUpdate'] ?? 0)) < 2) {
        return;
    }

    $previousWeather = $env['weather'];

    $weatherStates = [
        'Ясно' => ['Ясно', 'Облачно', 'Шторм', 'Снег'],
        'Облачно' => ['Облачно', 'Ясно', 'Шторм', 'Снег'],
        'Шторм' => ['Шторм', 'Облачно', 'Снег'],
        'Снег' => ['Снег', 'Облачно', 'Ясно']
    ];

    $weights = [
        'Ясно' => [0.7, 0.2, 0.07, 0.03],
        'Облачно' => [0.4, 0.25, 0.2, 0.15],
        'Шторм' => [0.55, 0.3, 0.15],
        'Снег' => [0.5, 0.3, 0.2]
    ];

    $options = $weatherStates[$env['weather']] ?? ['Ясно'];
    $probabilities = $weights[$env['weather']] ?? [1];
    $rand = mt_rand() / mt_getrandmax();
    $cumulative = 0;
    foreach ($options as $index => $weather) {
        $cumulative += $probabilities[$index] ?? 0;
        if ($rand <= $cumulative) {
            $env['weather'] = $weather;
            break;
        }
    }

    if (!in_array($env['weather'], $options, true)) {
        $env['weather'] = end($options);
        reset($options);
    }

    if ($env['weather'] !== $previousWeather) {
        $messages = [
            'Шторм' => 'Внимание: спутниковая зона попала в грозовой фронт.',
            'Облачно' => 'Наблюдается облачность, возможны колебания сигнала.',
            'Снег' => 'Снегопад усиливается — сигналу требуется корректировка.',
            'Ясно' => 'Небо прояснилось. Помехи минимальны.'
        ];
        if (isset($messages[$env['weather']])) {
            logEvent($state, $messages[$env['weather']]);
        }
    }

    $solarLevels = ['Низкая', 'Умеренная', 'Повышенная', 'Высокая'];
    $env['solarActivity'] = $solarLevels[array_rand($solarLevels)];

    $baseInterference = match ($env['weather']) {
        'Шторм' => 28,
        'Снег' => 20,
        'Облачно' => 14,
        default => 8,
    };
    $solarBonus = match ($env['solarActivity']) {
        'Высокая' => 14,
        'Повышенная' => 8,
        'Умеренная' => 4,
        default => 0,
    };
    $env['interference'] = max(2, min(42, (int) round($baseInterference + $solarBonus + mt_rand(-4, 4))));

    $latencyBase = 460 + mt_rand(-40, 60);
    if ($env['weather'] === 'Шторм') {
        $latencyBase += 140;
    } elseif ($env['weather'] === 'Снег') {
        $latencyBase += 90;
    }
    $env['latency'] = max(420, min(920, (int) round($latencyBase + $env['interference'] * 1.4)));

    $packetLossBase = 0.2 + $env['interference'] / 60;
    if ($env['solarActivity'] === 'Высокая') {
        $packetLossBase += 1.2;
    }
    if ($env['weather'] === 'Шторм') {
        $packetLossBase += 1.6;
    }
    $env['packetLoss'] = round(min(12, $packetLossBase + mt_rand(0, 10) / 10), 1);

    $env['powerLoad'] = max(20, min(95, $env['powerLoad'] + mt_rand(-2, 4)));
    $env['temperature'] = max(-40, min(35, $env['temperature'] + mt_rand(-1, 1)));

    $env['lastUpdate'] = $now;

    $state['environment'] = $env;
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

function findOptimalSatellite(array $state): ?array {
    $best = null;
    $bestScore = -INF;
    foreach ($state['satellites'] as $sat) {
        $baseline = ['quality' => 100, 'speed' => $sat['bandwidth']];
        $projected = applyEnvironmentEffects($baseline, $state['environment']);
        $score = $projected['speed'] - abs($sat['longitude']);
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $sat;
        }
    }
    return $best;
}

function refreshClients(array &$state, array $signal): void {
    foreach ($state['clients'] as &$client) {
        if (mt_rand(0, 100) > 88) {
            $client['demand'] = max(40, min(180, $client['demand'] + mt_rand(-6, 12)));
        }
        if ($signal['quality'] < 30) {
            $client['buffer'] = max(0, round($client['buffer'] - mt_rand(1, 4) / 2, 1));
        }
    }
}

switch ($action) {
    case 'status':
        updateEnvironment($state);
        $targetSat = $state['targetSatellite'] ? findSatellite($state, $state['targetSatellite']) : null;
        $signal = $targetSat ? calculateSignal($state['orientation'], $targetSat) : ['quality' => 0, 'speed' => 0];
        $signal = applyEnvironmentEffects($signal, $state['environment']);
        distributeTraffic($state, $signal, $state['targetSatellite']);
        refreshClients($state, $signal);
        $_SESSION['sim_state'] = $state;
        response([
            'orientation' => $state['orientation'],
            'targetSatellite' => $state['targetSatellite'],
            'satellites' => $state['satellites'],
            'clients' => $state['clients'],
            'signal' => $signal,
            'linkLocked' => $state['linkLocked'],
            'events' => $state['eventLog'],
            'environment' => [
                'weather' => $state['environment']['weather'],
                'solarActivity' => $state['environment']['solarActivity'],
                'interference' => $state['environment']['interference'],
                'latency' => $state['environment']['latency'],
                'packetLoss' => $state['environment']['packetLoss']
            ]
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
            $signal = applyEnvironmentEffects($signal, $state['environment']);
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
    case 'auto-optimize':
        updateEnvironment($state);
        $best = findOptimalSatellite($state);
        if ($best) {
            $state['targetSatellite'] = $best['id'];
            $state['orientation'] = [
                'azimuth' => $best['azimuth'],
                'elevation' => $best['elevation']
            ];
            logEvent($state, 'Автонаведение выбрало спутник ' . $best['name'] . '.');
        }
        $_SESSION['sim_state'] = $state;
        response([
            'targetSatellite' => $state['targetSatellite'],
            'orientation' => $state['orientation']
        ]);
    case 'run-drill':
        logEvent($state, 'Учебная тревога: выполняется аварийное переключение.');
        $state['linkLocked'] = false;
        foreach ($state['clients'] as &$client) {
            $client['status'] = 'Учения...';
            $client['buffer'] = max(0, round($client['buffer'] * 0.85, 1));
        }
        $state['orientation'] = [
            'azimuth' => mt_rand(0, 360),
            'elevation' => mt_rand(10, 70)
        ];
        $_SESSION['sim_state'] = $state;
        response([
            'orientation' => $state['orientation'],
            'clients' => $state['clients']
        ]);
    case 'reset':
        $_SESSION['sim_state'] = baseState();
        response(['reset' => true]);
    default:
        response(['error' => 'Неизвестное действие']);
}
