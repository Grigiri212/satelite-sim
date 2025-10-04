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

function systemsDefaults(): array {
    return [
        'backupRelayActive' => false,
        'spectrumStatus' => 'Ожидает запуск',
        'loadProfile' => 'Номинальный',
        'clockOffset' => 1.2,
        'fieldTeam' => 'На базе',
        'softwareVersion' => '3.2.1',
        'thermalMode' => 'Пассивное охлаждение',
        'lastCalibration' => null,
        'redundantChannel' => 'Готов',
        'nightShift' => false
    ];
}

function environmentDefaults(): array {
    return [
        'weather' => 'Ясно',
        'solarActivity' => 'Низкая',
        'interference' => 6,
        'latency' => 520,
        'packetLoss' => 0.4,
        'powerLoad' => 34,
        'temperature' => -5,
        'radiation' => 0.8,
        'wind' => 6,
        'lastUpdate' => microtime(true)
    ];
}

function datacenterDefaults(): array {
    return [
        'lastUpdate' => microtime(true),
        'power' => [
            'gridLoad' => 62,
            'upsCharge' => 96,
            'pduLoad' => 58,
            'coolingPower' => 39,
            'generatorState' => 'Готов'
        ],
        'cooling' => [
            'supplyTemp' => 23.5,
            'returnTemp' => 30.2,
            'humidity' => 46,
            'airflow' => 68,
            'freeCooling' => true,
            'status' => 'Норма'
        ],
        'virtualization' => [
            'automation' => 'Автопилот распределения включен',
            'clusters' => [
                [
                    'id' => 'alpha',
                    'name' => 'Кластер «Alpha»',
                    'cpu' => 68,
                    'memory' => 72,
                    'storage' => 61,
                    'status' => 'Норма'
                ],
                [
                    'id' => 'beta',
                    'name' => 'Кластер «Beta»',
                    'cpu' => 54,
                    'memory' => 59,
                    'storage' => 48,
                    'status' => 'Норма'
                ],
                [
                    'id' => 'gamma',
                    'name' => 'Кластер «Gamma»',
                    'cpu' => 73,
                    'memory' => 67,
                    'storage' => 58,
                    'status' => 'Норма'
                ]
            ]
        ],
        'racks' => [
            [
                'id' => 'A1',
                'label' => 'Стойка A1',
                'load' => 65,
                'thermal' => 27.4,
                'status' => 'Норма',
                'powerFeed' => 'PDU-1'
            ],
            [
                'id' => 'A2',
                'label' => 'Стойка A2',
                'load' => 58,
                'thermal' => 26.1,
                'status' => 'Норма',
                'powerFeed' => 'PDU-1'
            ],
            [
                'id' => 'B1',
                'label' => 'Стойка B1',
                'load' => 71,
                'thermal' => 28.3,
                'status' => 'Норма',
                'powerFeed' => 'PDU-2'
            ],
            [
                'id' => 'B2',
                'label' => 'Стойка B2',
                'load' => 63,
                'thermal' => 29.0,
                'status' => 'Норма',
                'powerFeed' => 'PDU-2'
            ]
        ],
        'operations' => [
            'maintenanceWindow' => '02:00–04:00',
            'lastDrill' => '12 часов назад',
            'tickets' => [
                [
                    'id' => 'INC-342',
                    'title' => 'Датчик температуры в стойке B2',
                    'status' => 'В работе'
                ],
                [
                    'id' => 'CHG-128',
                    'title' => 'Апгрейд гипервизора кластера «Gamma»',
                    'status' => 'Ожидает'
                ]
            ]
        ],
        'alarms' => []
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
    ], [
        'timestamp' => date('H:i:s'),
        'message' => 'Датацентр работает в штатном режиме, все кластеры синхронизированы.'
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
        'environment' => environmentDefaults(),
        'systems' => systemsDefaults(),
        'alerts' => [],
        'scenarioStates' => [],
        'datacenter' => datacenterDefaults()
    ];
}

if (!isset($_SESSION['sim_state'])) {
    $_SESSION['sim_state'] = baseState();
}

$state = $_SESSION['sim_state'];

if (!isset($state['systems'])) {
    $state['systems'] = systemsDefaults();
}

if (!isset($state['alerts'])) {
    $state['alerts'] = [];
}

if (!isset($state['scenarioStates'])) {
    $state['scenarioStates'] = [];
}

$state['environment'] = array_merge(environmentDefaults(), $state['environment'] ?? []);

if (!isset($state['datacenter']) || !is_array($state['datacenter'])) {
    $state['datacenter'] = datacenterDefaults();
} else {
    $state['datacenter'] = array_replace_recursive(datacenterDefaults(), $state['datacenter']);
}

function clampValue(float $value, float $min, float $max): float
{
    return max($min, min($max, $value));
}

function updateDataCenter(array &$state): void
{
    if (!isset($state['datacenter']) || !is_array($state['datacenter'])) {
        $state['datacenter'] = datacenterDefaults();
    }

    $dc = $state['datacenter'];
    $now = microtime(true);
    if (($now - ($dc['lastUpdate'] ?? 0)) < 2) {
        return;
    }

    $dc['power']['gridLoad'] = clampValue($dc['power']['gridLoad'] + mt_rand(-2, 3), 42, 94);
    $dc['power']['pduLoad'] = clampValue($dc['power']['pduLoad'] + mt_rand(-3, 3), 35, 92);
    $dc['power']['coolingPower'] = clampValue($dc['power']['coolingPower'] + mt_rand(-2, 2), 20, 70);
    $dc['power']['upsCharge'] = clampValue($dc['power']['upsCharge'] + mt_rand(-1, 1) / 2, 38, 100);

    if ($dc['power']['gridLoad'] > 88 && $dc['power']['generatorState'] === 'Готов') {
        $dc['power']['generatorState'] = 'Режим ожидания';
    } elseif ($dc['power']['gridLoad'] < 75 && $dc['power']['generatorState'] === 'Режим ожидания') {
        $dc['power']['generatorState'] = 'Готов';
    } elseif ($dc['power']['generatorState'] === 'Тестируется' && $dc['power']['upsCharge'] > 80) {
        $dc['power']['generatorState'] = 'Готов';
    }

    $dc['cooling']['supplyTemp'] = clampValue($dc['cooling']['supplyTemp'] + mt_rand(-2, 2) / 10, 18, 29);
    $dc['cooling']['returnTemp'] = clampValue($dc['cooling']['returnTemp'] + mt_rand(-3, 3) / 10, 24, 36);
    $dc['cooling']['humidity'] = clampValue($dc['cooling']['humidity'] + mt_rand(-2, 2), 32, 62);
    $dc['cooling']['airflow'] = clampValue($dc['cooling']['airflow'] + mt_rand(-3, 4), 40, 95);

    if ($dc['cooling']['supplyTemp'] > 26) {
        $dc['cooling']['status'] = 'Перегрев';
    } elseif ($dc['cooling']['supplyTemp'] < 20) {
        $dc['cooling']['status'] = 'Пониженная температура';
    } elseif ($dc['cooling']['status'] !== 'Охлаждение усилено') {
        $dc['cooling']['status'] = 'Норма';
    }

    if ($dc['virtualization']['automation'] === 'Балансировка выполнена') {
        $dc['virtualization']['automation'] = 'Автопилот распределения включен';
    }

    foreach ($dc['virtualization']['clusters'] as &$cluster) {
        $cluster['cpu'] = clampValue($cluster['cpu'] + mt_rand(-4, 4), 28, 94);
        $cluster['memory'] = clampValue($cluster['memory'] + mt_rand(-3, 4), 30, 96);
        $cluster['storage'] = clampValue($cluster['storage'] + mt_rand(-2, 3), 22, 90);

        if ($cluster['cpu'] > 82 || $cluster['memory'] > 84) {
            $cluster['status'] = 'Напряжение';
        } elseif ($cluster['cpu'] < 45 && $cluster['memory'] < 48) {
            $cluster['status'] = 'Резерв';
        } elseif ($cluster['status'] !== 'Балансировка') {
            $cluster['status'] = 'Норма';
        }
    }
    unset($cluster);

    foreach ($dc['racks'] as &$rack) {
        $rack['load'] = clampValue($rack['load'] + mt_rand(-4, 5), 28, 96);
        $rack['thermal'] = clampValue($rack['thermal'] + mt_rand(-2, 3) / 10, 22, 35);
        if ($rack['thermal'] >= 32) {
            $rack['status'] = 'Повышена температура';
        } elseif ($rack['load'] >= 85) {
            $rack['status'] = 'Высокая нагрузка';
        } else {
            $rack['status'] = 'Норма';
        }
    }
    unset($rack);

    if (isset($dc['operations']['tickets']) && is_array($dc['operations']['tickets'])) {
        foreach ($dc['operations']['tickets'] as &$ticket) {
            if ($ticket['status'] === 'В работе' && mt_rand(0, 100) > 92) {
                $ticket['status'] = 'Завершено';
            } elseif ($ticket['status'] === 'Ожидает' && mt_rand(0, 100) > 88) {
                $ticket['status'] = 'В работе';
            }
        }
        unset($ticket);
    }

    $alarms = [];
    if ($dc['power']['gridLoad'] > 88) {
        $alarms[] = 'Сеть на пределе — подготовьте генераторы.';
    }
    if ($dc['cooling']['status'] === 'Перегрев') {
        $alarms[] = 'Температура подачи растет, усилите охлаждение.';
    }
    foreach ($dc['racks'] as $rackInfo) {
        if ($rackInfo['thermal'] >= 32) {
            $alarms[] = 'Стойка ' . $rackInfo['label'] . ': температура ' . round($rackInfo['thermal'], 1) . '°C';
        }
    }
    if ($dc['cooling']['humidity'] > 58) {
        $alarms[] = 'Повышенная влажность в машинном зале.';
    }

    $dc['alarms'] = array_slice(array_unique($alarms), 0, 5);
    $dc['lastUpdate'] = $now;
    $state['datacenter'] = $dc;
}

function runDatacenterOperation(array &$state, string $operation): array
{
    if (!isset($state['datacenter']) || !is_array($state['datacenter'])) {
        $state['datacenter'] = datacenterDefaults();
    }

    $dc =& $state['datacenter'];

    switch ($operation) {
        case 'balance-vms':
            foreach ($dc['virtualization']['clusters'] as &$cluster) {
                $cluster['cpu'] = clampValue($cluster['cpu'] - mt_rand(6, 12), 20, 92);
                $cluster['memory'] = clampValue($cluster['memory'] - mt_rand(4, 9), 20, 92);
                $cluster['status'] = 'Балансировка';
            }
            unset($cluster);
            $dc['virtualization']['automation'] = 'Балансировка выполнена';
            logEvent($state, 'Датацентр: выполнена балансировка виртуальных машин.');
            return ['success' => true, 'message' => 'Нагрузка кластеров перераспределена.'];
        case 'boost-cooling':
            $dc['cooling']['supplyTemp'] = clampValue($dc['cooling']['supplyTemp'] - 1.8, 16, 29);
            $dc['cooling']['returnTemp'] = clampValue($dc['cooling']['returnTemp'] - 1.2, 22, 34);
            $dc['cooling']['airflow'] = clampValue($dc['cooling']['airflow'] + 6, 40, 100);
            $dc['cooling']['freeCooling'] = true;
            $dc['cooling']['status'] = 'Охлаждение усилено';
            logEvent($state, 'Датацентр: усилено охлаждение серверных залов.');
            pushAlert($state, 'info', 'Система охлаждения ЦОДа увеличивает поток воздуха.');
            return ['success' => true, 'message' => 'Охлаждение усилено.'];
        case 'test-generators':
            $dc['power']['generatorState'] = 'Тестируется';
            $dc['power']['upsCharge'] = clampValue($dc['power']['upsCharge'] - 4, 35, 100);
            $dc['operations']['lastDrill'] = 'Только что';
            logEvent($state, 'Датацентр: проведена проверка дизель-генераторов.');
            pushAlert($state, 'warning', 'Идёт тестирование дизель-генераторов ЦОДа.');
            return ['success' => true, 'message' => 'Тестирование генераторов запущено.'];
        case 'ack-alarms':
            $dc['alarms'] = [];
            logEvent($state, 'Датацентр: предупреждения подтверждены оператором.');
            return ['success' => true, 'message' => 'Предупреждения сброшены.'];
        default:
            return ['success' => false, 'message' => 'Неизвестная операция'];
    }
}

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

function pushAlert(array &$state, string $severity, string $message): void {
    if (!isset($state['alerts'])) {
        $state['alerts'] = [];
    }
    $state['alerts'][] = [
        'id' => uniqid('alert_', true),
        'severity' => $severity,
        'message' => $message,
        'time' => date('H:i')
    ];
    if (count($state['alerts']) > 10) {
        $state['alerts'] = array_slice($state['alerts'], -10);
    }
}

function recordScenarioResult(array &$state, string $scenarioId, string $summary): void {
    if (!isset($state['scenarioStates'])) {
        $state['scenarioStates'] = [];
    }
    $state['scenarioStates'][$scenarioId] = [
        'timestamp' => date('H:i:s'),
        'summary' => $summary
    ];
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
    $env['radiation'] = max(0.2, min(8, round($env['radiation'] + mt_rand(-2, 2) / 10, 1)));
    $env['wind'] = max(0, min(28, $env['wind'] + mt_rand(-2, 3)));

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

function simulateStormFront(array &$state): string {
    $state['environment']['weather'] = 'Шторм';
    $state['environment']['interference'] = min(42, $state['environment']['interference'] + 10);
    $state['environment']['packetLoss'] = min(12, $state['environment']['packetLoss'] + 2.4);
    $state['environment']['latency'] = min(980, $state['environment']['latency'] + 180);
    $state['environment']['wind'] = min(28, $state['environment']['wind'] + 6);
    foreach ($state['clients'] as &$client) {
        $client['status'] = 'Ожидает стабилизации';
        $client['buffer'] = max(0, round($client['buffer'] * 0.85, 1));
    }
    $state['systems']['loadProfile'] = 'Перегрузка из-за шторма';
    logEvent($state, 'Штормовой фронт: повышенные помехи и задержки.');
    pushAlert($state, 'critical', 'Грозовой фронт в зоне спутника — требуется коррекция ориентации.');
    return 'Грозовой фронт смоделирован. Станции необходимо снизить нагрузку.';
}

function simulateSolarFlare(array &$state): string {
    $state['environment']['solarActivity'] = 'Высокая';
    $state['environment']['interference'] = min(42, $state['environment']['interference'] + 8);
    $state['environment']['radiation'] = min(8, $state['environment']['radiation'] + 1.5);
    $state['systems']['clockOffset'] = max(0.2, $state['systems']['clockOffset'] + 0.8);
    logEvent($state, 'Зафиксирована вспышка на Солнце. Индукционные токи растут.');
    pushAlert($state, 'warning', 'Повышенная солнечная активность — включите запасные фильтры.');
    return 'Вспышка на Солнце смоделирована, параметры обновлены.';
}

function deployBackupRelay(array &$state): string {
    $state['systems']['backupRelayActive'] = true;
    $state['systems']['redundantChannel'] = 'Активирован';
    $state['environment']['interference'] = max(2, $state['environment']['interference'] - 4);
    $state['environment']['packetLoss'] = max(0.3, $state['environment']['packetLoss'] - 1.1);
    logEvent($state, 'Запущен резервный ретранслятор. Помехи снижены.');
    pushAlert($state, 'info', 'Резервный ретранслятор обеспечивает устойчивость канала.');
    return 'Резервный канал связи задействован.';
}

function performSpectrumSweep(array &$state): string {
    $state['systems']['spectrumStatus'] = 'Сканирование выполнено';
    $state['systems']['lastCalibration'] = date('H:i');
    $state['environment']['interference'] = max(2, $state['environment']['interference'] - 2);
    logEvent($state, 'Анализ спектра завершен, выявлены узкие полосы помех.');
    return 'Спектральный анализ выполнен, таблица помех обновлена.';
}

function triggerLoadBalancing(array &$state): string {
    $state['systems']['loadProfile'] = 'Динамическое перераспределение';
    $totalDemand = array_sum(array_column($state['clients'], 'demand'));
    foreach ($state['clients'] as &$client) {
        $share = $totalDemand > 0 ? $client['demand'] / $totalDemand : 0;
        $client['buffer'] = round($client['buffer'] + $share * 6, 1);
        $client['status'] = 'Балансировка канала';
    }
    logEvent($state, 'Выполнена балансировка потоков данных между абонентами.');
    return 'Нагрузка перераспределена, приоритеты обновлены.';
}

function executeClockResync(array &$state): string {
    $state['systems']['clockOffset'] = 0.1;
    $state['orientation']['azimuth'] = max(0, min(360, $state['orientation']['azimuth'] + mt_rand(-2, 2)));
    $state['orientation']['elevation'] = max(0, min(90, $state['orientation']['elevation'] + mt_rand(-1, 1)));
    logEvent($state, 'Проведена ресинхронизация опорного генератора.');
    pushAlert($state, 'info', 'Часы станции пересинхронизированы, смещение минимально.');
    return 'Ресинхронизация завершена, смещение устранено.';
}

function dispatchFieldRepair(array &$state): string {
    $state['systems']['fieldTeam'] = 'В маршруте';
    logEvent($state, 'Выездная бригада отправлена к объекту для проверки зеркала антенны.');
    pushAlert($state, 'warning', 'Бригада в пути. Прогноз прибытия 25 минут.');
    return 'Команда обслуживания направлена к антенной площадке.';
}

function performSoftwarePatch(array &$state): string {
    $currentVersion = $state['systems']['softwareVersion'];
    $parts = explode('.', $currentVersion);
    if (count($parts) === 3) {
        $parts[2] = (string) ((int) $parts[2] + 1);
        $state['systems']['softwareVersion'] = implode('.', $parts);
    } else {
        $state['systems']['softwareVersion'] = $currentVersion . '.1';
    }
    $state['systems']['lastCalibration'] = date('H:i');
    logEvent($state, 'Установлено обновление ПО станции до версии ' . $state['systems']['softwareVersion'] . '.');
    return 'Программное обеспечение обновлено, параметры сохранены.';
}

function engageThermalControl(array &$state): string {
    $state['systems']['thermalMode'] = 'Активное охлаждение';
    $state['environment']['temperature'] = max(-40, $state['environment']['temperature'] - 4);
    logEvent($state, 'Активирована система жидкостного охлаждения антенны.');
    return 'Температура опорных блоков снижена активным охлаждением.';
}

function simulateFiberCut(array &$state): string {
    foreach ($state['clients'] as &$client) {
        if (mt_rand(0, 100) < 60) {
            $client['status'] = 'Потеря магистрали';
            $client['buffer'] = max(0, round($client['buffer'] * 0.6, 1));
        }
    }
    $state['systems']['redundantChannel'] = 'Перенаправление трафика';
    logEvent($state, 'Обрыв оптоволокна: трафик перенаправлен через спутник.');
    pushAlert($state, 'critical', 'Основная магистраль недоступна — трафик идет через спутник.');
    return 'Авария магистрали смоделирована, включен резервный маршрут.';
}

function toggleNightOperations(array &$state): string {
    $state['systems']['nightShift'] = !$state['systems']['nightShift'];
    if ($state['systems']['nightShift']) {
        $state['environment']['powerLoad'] = max(15, $state['environment']['powerLoad'] - 12);
        logEvent($state, 'Переход на ночной режим работы. Нагрузка снижена.');
        return 'Ночной режим включен. Расход энергии снижен.';
    }
    $state['environment']['powerLoad'] = min(95, $state['environment']['powerLoad'] + 10);
    logEvent($state, 'Возвращение к дневному режиму работы.');
    return 'Ночной режим отключен, возвращаемся к дневной схеме.';
}

function runScenario(array &$state, string $scenario): array {
    $handlers = [
        'storm-front' => 'simulateStormFront',
        'solar-flare' => 'simulateSolarFlare',
        'backup-relay' => 'deployBackupRelay',
        'spectrum-scan' => 'performSpectrumSweep',
        'load-balance' => 'triggerLoadBalancing',
        'clock-resync' => 'executeClockResync',
        'field-team' => 'dispatchFieldRepair',
        'software-patch' => 'performSoftwarePatch',
        'thermal-control' => 'engageThermalControl',
        'fiber-cut' => 'simulateFiberCut',
        'night-ops' => 'toggleNightOperations'
    ];

    if (!isset($handlers[$scenario])) {
        return ['success' => false, 'message' => 'Неизвестная симуляция'];
    }

    $callback = $handlers[$scenario];
    $message = $callback($state);
    recordScenarioResult($state, $scenario, $message);

    return ['success' => true, 'message' => $message];
}

switch ($action) {
    case 'status':
        updateEnvironment($state);
        updateDataCenter($state);
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
                'packetLoss' => $state['environment']['packetLoss'],
                'powerLoad' => $state['environment']['powerLoad'],
                'temperature' => $state['environment']['temperature'],
                'radiation' => $state['environment']['radiation'],
                'wind' => $state['environment']['wind']
            ],
            'systems' => $state['systems'],
            'alerts' => $state['alerts'],
            'scenarioStates' => $state['scenarioStates'],
            'datacenter' => $state['datacenter']
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
    case 'run-scenario':
        $scenario = $_POST['scenario'] ?? '';
        $result = runScenario($state, $scenario);
        $_SESSION['sim_state'] = $state;
        response($result);
    case 'datacenter-action':
        $operation = $_POST['operation'] ?? '';
        $result = runDatacenterOperation($state, $operation);
        updateDataCenter($state);
        $_SESSION['sim_state'] = $state;
        response($result);
    case 'reset':
        $_SESSION['sim_state'] = baseState();
        response(['reset' => true]);
    default:
        response(['error' => 'Неизвестное действие']);
}
