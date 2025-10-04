const satelliteList = document.getElementById('satellite-list');
const clientsList = document.getElementById('clients-list');
const eventLog = document.getElementById('event-log');
const activeSatelliteEl = document.getElementById('active-satellite');
const signalLevelEl = document.getElementById('signal-level');
const linkSpeedEl = document.getElementById('link-speed');
const latencyEl = document.getElementById('latency');
const packetLossEl = document.getElementById('packet-loss');
const weatherStatusEl = document.getElementById('weather-status');
const solarActivityEl = document.getElementById('solar-activity');
const interferenceLevelEl = document.getElementById('interference-level');
const azimuthSlider = document.getElementById('azimuth');
const elevationSlider = document.getElementById('elevation');
const azimuthValue = document.getElementById('azimuth-value');
const elevationValue = document.getElementById('elevation-value');
const alignButton = document.getElementById('align-button');
const scanButton = document.getElementById('scan-button');
const autoButton = document.getElementById('auto-button');
const drillButton = document.getElementById('drill-button');
const radarCanvas = document.getElementById('radar-display');
const ctx = radarCanvas.getContext('2d');

let state = {
    satellites: [],
    clients: [],
    targetSatellite: null,
    orientation: { azimuth: 180, elevation: 45 },
    signal: { quality: 0, speed: 0 },
    events: [],
    linkLocked: false,
    environment: {
        weather: '—',
        solarActivity: '—',
        interference: 0,
        latency: 0,
        packetLoss: 0
    }
};

let scanInterval = null;

function fetchStatus() {
    return fetch('api.php?action=status')
        .then(res => res.json())
        .then(data => {
            state = { ...state, ...data };
            render();
        })
        .catch(err => console.error('Ошибка запроса статуса', err));
}

function setOrientation(azimuth, elevation) {
    const formData = new FormData();
    formData.append('azimuth', azimuth);
    formData.append('elevation', elevation);

    return fetch('api.php?action=set-orientation', {
        method: 'POST',
        body: formData
    }).then(() => fetchStatus());
}

function setTarget(satelliteId) {
    const formData = new FormData();
    formData.append('satellite', satelliteId);

    return fetch('api.php?action=set-target', {
        method: 'POST',
        body: formData
    }).then(() => fetchStatus());
}

function renderSatellites() {
    satelliteList.innerHTML = '';
    state.satellites.forEach(sat => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${sat.name}</td>
            <td>${sat.longitude.toFixed(1)}°E</td>
            <td>${sat.azimuth.toFixed(0)}°</td>
            <td>${sat.elevation.toFixed(0)}°</td>
            <td>${sat.bandwidth} </td>
            <td><button data-sat="${sat.id}">${state.targetSatellite === sat.id ? 'Активный' : 'Выбрать'}</button></td>
        `;
        const button = tr.querySelector('button');
        if (state.targetSatellite === sat.id) {
            button.classList.add('active');
            button.disabled = true;
        } else {
            button.addEventListener('click', () => setTarget(sat.id));
        }
        satelliteList.appendChild(tr);
    });
}

function renderClients() {
    clientsList.innerHTML = '';
    state.clients.forEach(client => {
        const progress = Math.min(100, Math.round((client.buffer / client.demand) * 100));
        const li = document.createElement('li');
        li.className = 'client-card';
        li.innerHTML = `
            <div>
                <strong>${client.name}</strong>
                <div class="client-status">${client.status}</div>
            </div>
            <div>
                <div class="client-status">Буфер: ${client.buffer} / ${client.demand} ГБ</div>
                <div class="progress-bar"><span style="width:${progress}%"></span></div>
            </div>
        `;
        clientsList.appendChild(li);
    });
}

function renderLog() {
    eventLog.innerHTML = '';
    state.events.slice().reverse().forEach(entry => {
        const li = document.createElement('li');
        li.innerHTML = `<time>${entry.timestamp}</time><span>${entry.message}</span>`;
        eventLog.appendChild(li);
    });
}

function renderMetrics() {
    const active = state.satellites.find(s => s.id === state.targetSatellite);
    activeSatelliteEl.textContent = active ? active.name : '—';
    signalLevelEl.textContent = `${state.signal.quality}%`;
    linkSpeedEl.textContent = `${state.signal.speed} Мбит/с`;
    signalLevelEl.classList.toggle('locked', state.linkLocked);
    linkSpeedEl.classList.toggle('locked', state.linkLocked);
    const latency = state.environment && typeof state.environment.latency !== 'undefined'
        ? state.environment.latency
        : null;
    const packetLoss = state.environment && typeof state.environment.packetLoss !== 'undefined'
        ? state.environment.packetLoss
        : null;
    latencyEl.textContent = latency === null ? '—' : `${latency} мс`;
    packetLossEl.textContent = packetLoss === null ? '—' : `${Number(packetLoss).toFixed(1)}%`;
}

function renderControls() {
    azimuthSlider.value = state.orientation.azimuth;
    elevationSlider.value = state.orientation.elevation;
    azimuthValue.textContent = `${Math.round(state.orientation.azimuth)}°`;
    elevationValue.textContent = `${Math.round(state.orientation.elevation)}°`;
}

function renderRadar() {
    const width = radarCanvas.width;
    const height = radarCanvas.height;
    ctx.clearRect(0, 0, width, height);

    ctx.strokeStyle = 'rgba(91,192,190,0.3)';
    ctx.lineWidth = 1;
    [60, 110, 160].forEach(radius => {
        ctx.beginPath();
        ctx.arc(width / 2, height, radius, Math.PI, Math.PI * 2);
        ctx.stroke();
    });

    ctx.beginPath();
    ctx.moveTo(width / 2, height);
    ctx.lineTo(width / 2, height - 180);
    ctx.moveTo(width / 2, height);
    ctx.lineTo(width / 2 - 160, height - 20);
    ctx.moveTo(width / 2, height);
    ctx.lineTo(width / 2 + 160, height - 20);
    ctx.stroke();

    const orientationAz = (state.orientation.azimuth % 360) * (Math.PI / 180);
    const orientationElev = state.orientation.elevation;
    const radius = (orientationElev / 90) * 160;
    const x = width / 2 + Math.sin(orientationAz) * radius;
    const y = height - Math.cos(orientationAz) * radius - (orientationElev / 90) * 80;

    ctx.fillStyle = 'rgba(91,192,190,0.9)';
    ctx.beginPath();
    ctx.arc(x, y, 6, 0, Math.PI * 2);
    ctx.fill();

    state.satellites.forEach(sat => {
        const az = (sat.azimuth % 360) * (Math.PI / 180);
        const r = (sat.elevation / 90) * 160;
        const sx = width / 2 + Math.sin(az) * r;
        const sy = height - Math.cos(az) * r - (sat.elevation / 90) * 80;
        ctx.fillStyle = sat.id === state.targetSatellite ? '#16c0a0' : 'rgba(255,255,255,0.5)';
        ctx.beginPath();
        ctx.arc(sx, sy, 4, 0, Math.PI * 2);
        ctx.fill();
    });
}

function render() {
    renderSatellites();
    renderClients();
    renderLog();
    renderMetrics();
    renderControls();
    renderRadar();
    renderEnvironment();
}

function renderEnvironment() {
    const env = state.environment || {};
    weatherStatusEl.textContent = `Погода: ${env.weather ?? '—'}`;
    solarActivityEl.textContent = `Солнце: ${env.solarActivity ?? '—'}`;
    const interferenceValue = env.interference;
    if (typeof interferenceValue === 'number') {
        interferenceLevelEl.textContent = `Помехи: ${interferenceValue} dB`;
    } else {
        interferenceLevelEl.textContent = 'Помехи: —';
    }
}

function smoothAlign(target) {
    const steps = 20;
    let currentStep = 0;
    const startAz = parseFloat(azimuthSlider.value);
    const startEl = parseFloat(elevationSlider.value);
    const deltaAz = (target.azimuth - startAz) / steps;
    const deltaEl = (target.elevation - startEl) / steps;

    function step() {
        currentStep++;
        const newAz = startAz + deltaAz * currentStep;
        const newEl = startEl + deltaEl * currentStep;
        azimuthSlider.value = newAz;
        elevationSlider.value = newEl;
        azimuthValue.textContent = `${Math.round(newAz)}°`;
        elevationValue.textContent = `${Math.round(newEl)}°`;
        setOrientation(newAz, newEl);
        if (currentStep < steps) {
            requestAnimationFrame(step);
        }
    }

    step();
}

alignButton.addEventListener('click', () => {
    if (!state.targetSatellite) return;
    const sat = state.satellites.find(s => s.id === state.targetSatellite);
    if (!sat) return;
    smoothAlign(sat);
});

scanButton.addEventListener('click', () => {
    if (scanInterval) {
        clearInterval(scanInterval);
    }
    let az = parseFloat(azimuthSlider.value);
    scanInterval = setInterval(() => {
        az = (az + 10) % 360;
        const el = 25 + 20 * Math.sin(az * Math.PI / 180);
        azimuthSlider.value = az;
        elevationSlider.value = el;
        azimuthValue.textContent = `${Math.round(az)}°`;
        elevationValue.textContent = `${Math.round(el)}°`;
        setOrientation(az, el);
        if (state.signal.quality > 70 && state.targetSatellite) {
            clearInterval(scanInterval);
            scanInterval = null;
        }
    }, 200);
});

autoButton.addEventListener('click', () => {
    if (autoButton.disabled) return;
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    autoButton.disabled = true;
    const defaultLabel = autoButton.textContent;
    autoButton.textContent = 'Расчет...';
    fetch('api.php?action=auto-optimize', { method: 'POST' })
        .then(() => fetchStatus())
        .finally(() => {
            autoButton.disabled = false;
            autoButton.textContent = defaultLabel;
        });
});

drillButton.addEventListener('click', () => {
    if (drillButton.disabled) return;
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    drillButton.disabled = true;
    const defaultLabel = drillButton.textContent;
    drillButton.textContent = 'Сценарий...';
    fetch('api.php?action=run-drill', { method: 'POST' })
        .then(() => fetchStatus())
        .finally(() => {
            drillButton.disabled = false;
            drillButton.textContent = defaultLabel;
        });
});

azimuthSlider.addEventListener('input', () => {
    const az = parseFloat(azimuthSlider.value);
    azimuthValue.textContent = `${Math.round(az)}°`;
    setOrientation(az, parseFloat(elevationSlider.value));
});

elevationSlider.addEventListener('input', () => {
    const el = parseFloat(elevationSlider.value);
    elevationValue.textContent = `${Math.round(el)}°`;
    setOrientation(parseFloat(azimuthSlider.value), el);
});

setInterval(fetchStatus, 2500);
fetchStatus();
