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
    <script src="assets/script.js"></script>
</body>
</html>
