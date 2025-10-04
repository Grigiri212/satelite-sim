<?php
session_start();

if (!isset($_SESSION['state_initialized'])) {
    $_SESSION['state_initialized'] = true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Симулятор наземной станции</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="app-header">
        <h1>Симулятор наземной станции спутниковой связи</h1>
        <div class="status-panel">
            <div class="metric">
                <span class="metric-label">Активный спутник:</span>
                <span id="active-satellite" class="metric-value">—</span>
            </div>
            <div class="metric">
                <span class="metric-label">Уровень сигнала:</span>
                <span id="signal-level" class="metric-value">0%</span>
            </div>
            <div class="metric">
                <span class="metric-label">Скорость канала:</span>
                <span id="link-speed" class="metric-value">0 Мбит/с</span>
            </div>
            <div class="metric">
                <span class="metric-label">Задержка:</span>
                <span id="latency" class="metric-value">—</span>
            </div>
            <div class="metric">
                <span class="metric-label">Потери пакетов:</span>
                <span id="packet-loss" class="metric-value">—</span>
            </div>
            <div class="metric wide">
                <span class="metric-label">Окружающая среда:</span>
                <span class="metric-value environment">
                    <span id="weather-status">—</span>
                    <span id="solar-activity">—</span>
                    <span id="interference-level">—</span>
                    <span id="power-load">—</span>
                    <span id="temperature">—</span>
                    <span id="radiation">—</span>
                    <span id="wind">—</span>
                </span>
            </div>
        </div>
    </header>
    <main class="layout">
        <section class="panel satellites">
            <h2>Геостационарные спутники</h2>
            <p class="panel-description">Выберите спутник и наведите антенну станции на его позицию по азимуту и углу места.</p>
            <table class="satellite-table">
                <thead>
                    <tr>
                        <th>Спутник</th>
                        <th>Долгота</th>
                        <th>Азимут</th>
                        <th>Угол места</th>
                        <th>Полоса, Мбит/с</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="satellite-list"></tbody>
            </table>
        </section>
        <section class="panel control">
            <h2>Пульт управления антенной</h2>
            <div class="joystick">
                <div class="gauge">
                    <label for="azimuth">Азимут</label>
                    <div class="slider-group">
                        <input type="range" id="azimuth" min="0" max="360" value="180">
                        <span id="azimuth-value" class="slider-value">180°</span>
                    </div>
                </div>
                <div class="gauge">
                    <label for="elevation">Угол места</label>
                    <div class="slider-group">
                        <input type="range" id="elevation" min="0" max="90" value="45">
                        <span id="elevation-value" class="slider-value">45°</span>
                    </div>
                </div>
                <button id="align-button" class="primary">Синхронизировать</button>
                <button id="scan-button" class="secondary">Быстрый скан</button>
                <button id="auto-button" class="accent">Автонаведение</button>
                <button id="drill-button" class="ghost">Учебная тревога</button>
            </div>
            <div class="radar">
                <canvas id="radar-display" width="360" height="360"></canvas>
            </div>
        </section>
        <section class="panel visualization">
            <h2>3D-обзор орбит</h2>
            <p class="panel-description">Наглядное отображение положения спутников, линий видимости и текущей цели станции.</p>
            <div id="space-view" class="space-view"></div>
            <div class="view-legend">
                <div class="legend-item">
                    <span class="legend-color legend-satellite"></span>
                    <span>Спутники на геостационарной орбите</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-line"></span>
                    <span>Линии видимости станции</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color legend-target"></span>
                    <span>Активный спутник и закреплённый канал</span>
                </div>
            </div>
        </section>
        <section class="panel operations">
            <h2>Сценарии и симуляции</h2>
            <p class="panel-description">Запускайте тренировочные сценарии и отслеживайте состояние вспомогательных систем станции.</p>
            <div id="scenario-grid" class="scenario-grid"></div>
            <div class="systems-block">
                <h3>Состояние систем</h3>
                <ul id="system-readouts" class="system-list"></ul>
            </div>
            <div class="alerts-block">
                <h3>Оповещения</h3>
                <ul id="alert-feed" class="alert-feed"></ul>
            </div>
        </section>
        <section class="panel datacenter">
            <h2>Центр обработки данных</h2>
            <p class="panel-description">Контроль инфраструктуры, обеспечивающей спутниковый шлюз: питание, охлаждение, виртуальные кластеры и заявки техников.</p>
            <div class="datacenter-grid">
                <article class="datacenter-card">
                    <h3>Электропитание</h3>
                    <ul class="datacenter-metrics">
                        <li><span>Нагрузка на сеть</span><strong id="dc-grid-load">—</strong></li>
                        <li><span>Заряд ИБП</span><strong id="dc-ups-charge">—</strong></li>
                        <li><span>Нагрузка PDU</span><strong id="dc-pdu-load">—</strong></li>
                        <li><span>Генераторы</span><strong id="dc-generator-state">—</strong></li>
                        <li><span>Питание охлаждения</span><strong id="dc-cooling-power">—</strong></li>
                    </ul>
                </article>
                <article class="datacenter-card">
                    <h3>Охлаждение</h3>
                    <ul class="datacenter-metrics">
                        <li><span>Подача</span><strong id="dc-supply-temp">—</strong></li>
                        <li><span>Обратка</span><strong id="dc-return-temp">—</strong></li>
                        <li><span>Влажность</span><strong id="dc-humidity">—</strong></li>
                        <li><span>Поток воздуха</span><strong id="dc-airflow">—</strong></li>
                        <li><span>Свободное охлаждение</span><strong id="dc-free-cooling">—</strong></li>
                        <li><span>Статус</span><strong id="dc-cooling-status">—</strong></li>
                    </ul>
                </article>
                <article class="datacenter-card">
                    <h3>Виртуальные кластеры</h3>
                    <p id="dc-automation-status" class="datacenter-subtitle">—</p>
                    <div id="dc-cluster-list" class="cluster-list"></div>
                </article>
            </div>
            <div class="datacenter-controls">
                <h3>Управление</h3>
                <div class="control-grid">
                    <button class="dc-action primary" data-dc-action="balance-vms">Балансировка VM</button>
                    <button class="dc-action secondary" data-dc-action="boost-cooling">Усилить охлаждение</button>
                    <button class="dc-action accent" data-dc-action="test-generators">Тест генераторов</button>
                    <button class="dc-action ghost" data-dc-action="ack-alarms">Сбросить предупреждения</button>
                </div>
            </div>
            <div class="datacenter-lists">
                <div class="rack-block">
                    <h3>Стойки и питание</h3>
                    <div id="dc-rack-list" class="rack-list"></div>
                </div>
                <div class="tickets-block">
                    <h3>Задачи обслуживания</h3>
                    <ul id="dc-ticket-list" class="ticket-list"></ul>
                    <div class="maintenance-meta">
                        <span>Окно обслуживания: <strong id="dc-maintenance-window">—</strong></span>
                        <span>Последние учения: <strong id="dc-last-drill">—</strong></span>
                    </div>
                </div>
                <div class="alerts-block dc-alerts">
                    <h3>Предупреждения ЦОДа</h3>
                    <ul id="dc-alarms" class="alert-feed"></ul>
                </div>
            </div>
        </section>
        <section class="panel clients">
            <h2>Наземная сеть</h2>
            <p class="panel-description">Мониторинг распределения потока данных по абонентам.</p>
            <ul id="clients-list" class="client-list"></ul>
            <div class="log">
                <h3>Журнал событий</h3>
                <ul id="event-log"></ul>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/three@0.159.0/build/three.min.js"></script>
    <script src="assets/script.js"></script>
</body>
</html>
