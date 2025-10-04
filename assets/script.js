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
const powerLoadEl = document.getElementById('power-load');
const temperatureEl = document.getElementById('temperature');
const radiationEl = document.getElementById('radiation');
const windEl = document.getElementById('wind');
const azimuthSlider = document.getElementById('azimuth');
const elevationSlider = document.getElementById('elevation');
const azimuthValue = document.getElementById('azimuth-value');
const elevationValue = document.getElementById('elevation-value');
const alignButton = document.getElementById('align-button');
const scanButton = document.getElementById('scan-button');
const autoButton = document.getElementById('auto-button');
const drillButton = document.getElementById('drill-button');
const scenarioGrid = document.getElementById('scenario-grid');
const systemReadouts = document.getElementById('system-readouts');
const alertFeed = document.getElementById('alert-feed');
const radarCanvas = document.getElementById('radar-display');
const ctx = radarCanvas.getContext('2d');
const spaceView = document.getElementById('space-view');
const dcGridLoadEl = document.getElementById('dc-grid-load');
const dcUpsChargeEl = document.getElementById('dc-ups-charge');
const dcPduLoadEl = document.getElementById('dc-pdu-load');
const dcGeneratorEl = document.getElementById('dc-generator-state');
const dcCoolingPowerEl = document.getElementById('dc-cooling-power');
const dcSupplyTempEl = document.getElementById('dc-supply-temp');
const dcReturnTempEl = document.getElementById('dc-return-temp');
const dcHumidityEl = document.getElementById('dc-humidity');
const dcAirflowEl = document.getElementById('dc-airflow');
const dcFreeCoolingEl = document.getElementById('dc-free-cooling');
const dcCoolingStatusEl = document.getElementById('dc-cooling-status');
const dcAutomationStatusEl = document.getElementById('dc-automation-status');
const dcClusterList = document.getElementById('dc-cluster-list');
const dcRackList = document.getElementById('dc-rack-list');
const dcTicketList = document.getElementById('dc-ticket-list');
const dcMaintenanceWindowEl = document.getElementById('dc-maintenance-window');
const dcLastDrillEl = document.getElementById('dc-last-drill');
const dcAlarmsList = document.getElementById('dc-alarms');
const dcControlButtons = document.querySelectorAll('[data-dc-action]');

const scenarioDefinitions = [
    {
        id: 'storm-front',
        title: 'Штормовой фронт',
        description: 'Имитация грозового фронта и резкого роста помех.'
    },
    {
        id: 'solar-flare',
        title: 'Солнечная вспышка',
        description: 'Повышенная радиация и рост индукционных токов.'
    },
    {
        id: 'backup-relay',
        title: 'Резервный ретранслятор',
        description: 'Включение резервного спутникового канала.'
    },
    {
        id: 'spectrum-scan',
        title: 'Скан спектра',
        description: 'Быстрый анализ радиопомех по диапазонам.'
    },
    {
        id: 'load-balance',
        title: 'Балансировка трафика',
        description: 'Перераспределение пропускной способности между абонентами.'
    },
    {
        id: 'clock-resync',
        title: 'Синхронизация часов',
        description: 'Корректировка дрейфа опорного генератора станции.'
    },
    {
        id: 'field-team',
        title: 'Выездная бригада',
        description: 'Отправка техников к антенной площадке.'
    },
    {
        id: 'software-patch',
        title: 'Обновление ПО',
        description: 'Установка патча и переконфигурация контроллеров.'
    },
    {
        id: 'thermal-control',
        title: 'Термоконтроль',
        description: 'Активное охлаждение и снижение температуры оборудования.'
    },
    {
        id: 'fiber-cut',
        title: 'Обрыв магистрали',
        description: 'Учебный сценарий переключения при потере наземного канала.'
    },
    {
        id: 'night-ops',
        title: 'Ночной режим',
        description: 'Снижение энергопотребления и перевод систем в ночную смену.'
    }
];

const scenarioRefs = new Map();

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
        packetLoss: 0,
        powerLoad: 0,
        temperature: 0,
        radiation: 0,
        wind: 0
    },
    systems: {},
    alerts: [],
    scenarioStates: {},
    datacenter: {
        power: {},
        cooling: {},
        virtualization: { clusters: [] },
        racks: [],
        operations: { tickets: [] },
        alarms: []
    }
};

let scanInterval = null;

const spaceState = {
    initialized: false,
    renderer: null,
    scene: null,
    camera: null,
    root: null,
    satellitesGroup: null,
    linesGroup: null,
    groundPosition: null,
    satelliteMeshes: new Map(),
    lineSegments: new Map(),
    pointer: { active: false, x: 0, y: 0, target: null },
    autoRotate: true
};

const EARTH_RADIUS = 10;
const ORBIT_RADIUS = 16;
const GROUND_COORDS = { lat: 55.75, lon: 37.61 };

function degToRad(value) {
    return (value * Math.PI) / 180;
}

function positionOnSphere(latDeg, lonDeg, radius) {
    const lat = degToRad(latDeg);
    const lon = degToRad(lonDeg);
    const x = radius * Math.cos(lat) * Math.cos(lon);
    const y = radius * Math.sin(lat);
    const z = radius * Math.cos(lat) * Math.sin(lon);
    return new THREE.Vector3(x, y, z);
}

function positionFromLongitude(lonDeg, radius) {
    const lon = degToRad(lonDeg);
    const x = radius * Math.cos(lon);
    const z = radius * Math.sin(lon);
    return new THREE.Vector3(x, 0, z);
}

function initSpaceVisualization() {
    if (spaceState.initialized || !spaceView || typeof THREE === 'undefined') {
        return;
    }

    const width = spaceView.clientWidth || spaceView.offsetWidth;
    const height = spaceView.clientHeight || 340;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(window.devicePixelRatio || 1);
    renderer.setSize(width, height, false);
    renderer.setClearColor(0x000000, 0);
    spaceView.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x040b16, 0.035);

    const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(24, 14, 24);

    const root = new THREE.Group();
    scene.add(root);

    const ambient = new THREE.AmbientLight(0xbfd4ff, 0.7);
    scene.add(ambient);

    const directional = new THREE.DirectionalLight(0xffffff, 0.8);
    directional.position.set(18, 22, 14);
    scene.add(directional);

    const earthMaterial = new THREE.MeshPhongMaterial({
        color: 0x10335a,
        emissive: 0x04111f,
        shininess: 40,
        transparent: true,
        opacity: 0.95
    });
    const earth = new THREE.Mesh(new THREE.SphereGeometry(EARTH_RADIUS, 48, 48), earthMaterial);
    root.add(earth);

    const atmosphere = new THREE.Mesh(
        new THREE.SphereGeometry(EARTH_RADIUS + 0.2, 32, 32),
        new THREE.MeshBasicMaterial({
            color: 0x5bc0be,
            transparent: true,
            opacity: 0.08,
            blending: THREE.AdditiveBlending
        })
    );
    root.add(atmosphere);

    const orbitMaterial = new THREE.MeshBasicMaterial({
        color: 0x54a7ff,
        transparent: true,
        opacity: 0.5,
        side: THREE.DoubleSide
    });
    const orbit = new THREE.Mesh(new THREE.RingGeometry(ORBIT_RADIUS - 0.1, ORBIT_RADIUS + 0.1, 128), orbitMaterial);
    orbit.rotation.x = Math.PI / 2;
    root.add(orbit);

    const orbitGlow = new THREE.Mesh(
        new THREE.RingGeometry(ORBIT_RADIUS - 0.4, ORBIT_RADIUS + 0.4, 128),
        new THREE.MeshBasicMaterial({
            color: 0x16c0a0,
            transparent: true,
            opacity: 0.08,
            side: THREE.DoubleSide
        })
    );
    orbitGlow.rotation.x = Math.PI / 2;
    root.add(orbitGlow);

    const starsGeometry = new THREE.BufferGeometry();
    const starVertices = [];
    for (let i = 0; i < 600; i++) {
        const radius = 80;
        const phi = Math.acos(2 * Math.random() - 1);
        const theta = 2 * Math.PI * Math.random();
        starVertices.push(
            radius * Math.sin(phi) * Math.cos(theta),
            radius * Math.cos(phi),
            radius * Math.sin(phi) * Math.sin(theta)
        );
    }
    starsGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
    const starsMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.35, transparent: true, opacity: 0.6 });
    const stars = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(stars);

    const satellitesGroup = new THREE.Group();
    const linesGroup = new THREE.Group();
    root.add(satellitesGroup);
    root.add(linesGroup);

    const groundPosition = positionOnSphere(GROUND_COORDS.lat, GROUND_COORDS.lon, EARTH_RADIUS + 0.1);
    const groundMarker = new THREE.Mesh(
        new THREE.SphereGeometry(0.35, 16, 16),
        new THREE.MeshStandardMaterial({ color: 0xffc857, emissive: 0x593500, emissiveIntensity: 0.8 })
    );
    groundMarker.position.copy(groundPosition);
    satellitesGroup.add(groundMarker);

    const pointerDown = event => {
        event.preventDefault();
        spaceState.pointer.active = true;
        spaceState.pointer.x = event.clientX;
        spaceState.pointer.y = event.clientY;
        spaceState.pointer.target = event.target;
        spaceState.autoRotate = false;
        if (spaceState.pointer.target && spaceState.pointer.target.setPointerCapture) {
            spaceState.pointer.target.setPointerCapture(event.pointerId);
        }
    };

    const pointerMove = event => {
        if (!spaceState.pointer.active) return;
        const deltaX = event.clientX - spaceState.pointer.x;
        const deltaY = event.clientY - spaceState.pointer.y;
        spaceState.pointer.x = event.clientX;
        spaceState.pointer.y = event.clientY;
        root.rotation.y += deltaX * 0.005;
        const newRotationX = root.rotation.x + deltaY * 0.003;
        root.rotation.x = Math.max(-Math.PI / 4, Math.min(Math.PI / 4, newRotationX));
    };

    const pointerUp = event => {
        spaceState.pointer.active = false;
        spaceState.autoRotate = true;
        if (spaceState.pointer.target && spaceState.pointer.target.releasePointerCapture) {
            spaceState.pointer.target.releasePointerCapture(event.pointerId);
        }
        spaceState.pointer.target = null;
    };

    renderer.domElement.addEventListener('pointerdown', pointerDown);
    renderer.domElement.addEventListener('pointermove', pointerMove);
    renderer.domElement.addEventListener('pointerup', pointerUp);
    renderer.domElement.addEventListener('pointerleave', () => {
        spaceState.pointer.active = false;
        spaceState.autoRotate = true;
    });
    window.addEventListener('pointerup', pointerUp);

    const resizeObserver = () => {
        if (!spaceState.initialized) return;
        const newWidth = spaceView.clientWidth || spaceView.offsetWidth;
        const newHeight = spaceView.clientHeight || 340;
        camera.aspect = newWidth / newHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(newWidth, newHeight, false);
    };

    window.addEventListener('resize', resizeObserver);
    resizeObserver();

    const animate = () => {
        requestAnimationFrame(animate);
        if (spaceState.autoRotate) {
            root.rotation.y += 0.0008;
        }
        earth.rotation.y += 0.0015;
        atmosphere.rotation.y += 0.0015;
        stars.rotation.y -= 0.0004;
        renderer.render(scene, camera);
    };
    animate();

    spaceState.initialized = true;
    spaceState.renderer = renderer;
    spaceState.scene = scene;
    spaceState.camera = camera;
    spaceState.root = root;
    spaceState.satellitesGroup = satellitesGroup;
    spaceState.linesGroup = linesGroup;
    spaceState.groundPosition = groundPosition;
    spaceState.satelliteMeshes = new Map();
    spaceState.lineSegments = new Map();
};

function updateSpaceVisualization() {
    if (!spaceView || typeof THREE === 'undefined') {
        return;
    }

    if (!spaceState.initialized) {
        initSpaceVisualization();
    }

    if (!spaceState.initialized) {
        return;
    }

    const activeIds = new Set();

    state.satellites.forEach(sat => {
        activeIds.add(sat.id);
        let mesh = spaceState.satelliteMeshes.get(sat.id);
        if (!mesh) {
            const material = new THREE.MeshStandardMaterial({
                color: 0xffffff,
                emissive: 0x0c1a27,
                emissiveIntensity: 0.5,
                metalness: 0.2,
                roughness: 0.35
            });
            mesh = new THREE.Mesh(new THREE.SphereGeometry(0.45, 20, 20), material);
            spaceState.satellitesGroup.add(mesh);
            spaceState.satelliteMeshes.set(sat.id, mesh);
        }

        const position = positionFromLongitude(sat.longitude, ORBIT_RADIUS);
        mesh.position.copy(position);

        const isActive = sat.id === state.targetSatellite;
        mesh.material.color.set(isActive ? 0x16c0a0 : 0xffffff);
        mesh.material.emissiveIntensity = isActive ? 0.9 : 0.4;
        mesh.scale.setScalar(isActive ? 1.4 : 1);

        let line = spaceState.lineSegments.get(sat.id);
        if (!line) {
            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.Float32BufferAttribute(new Float32Array(6), 3));
            const material = new THREE.LineBasicMaterial({
                color: isActive ? 0x16c0a0 : 0x4a6cff,
                transparent: true,
                opacity: isActive ? 0.95 : 0.45
            });
            line = new THREE.Line(geometry, material);
            spaceState.linesGroup.add(line);
            spaceState.lineSegments.set(sat.id, line);
        }

        const attribute = line.geometry.getAttribute('position');
        const array = attribute.array;
        array[0] = spaceState.groundPosition.x;
        array[1] = spaceState.groundPosition.y;
        array[2] = spaceState.groundPosition.z;
        array[3] = position.x;
        array[4] = position.y;
        array[5] = position.z;
        attribute.needsUpdate = true;
        line.material.color.set(isActive ? 0x16c0a0 : 0x4a6cff);
        line.material.opacity = isActive ? 0.95 : 0.4;
    });

    spaceState.satelliteMeshes.forEach((mesh, id) => {
        if (!activeIds.has(id)) {
            spaceState.satellitesGroup.remove(mesh);
            mesh.geometry.dispose();
            mesh.material.dispose();
            spaceState.satelliteMeshes.delete(id);
        }
    });

    spaceState.lineSegments.forEach((line, id) => {
        if (!activeIds.has(id)) {
            spaceState.linesGroup.remove(line);
            line.geometry.dispose();
            line.material.dispose();
            spaceState.lineSegments.delete(id);
        }
    });
}

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

function runScenario(id, button) {
    if (!button) return;
    const defaultLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Выполнение...';
    const formData = new FormData();
    formData.append('scenario', id);
    fetch('api.php?action=run-scenario', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(() => fetchStatus())
        .finally(() => {
            button.disabled = false;
            button.textContent = defaultLabel;
        });
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
    if (powerLoadEl) {
        powerLoadEl.textContent = `Энергопотребление: ${env.powerLoad ?? '—'}%`;
    }
    if (temperatureEl) {
        temperatureEl.textContent = `Температура: ${typeof env.temperature === 'number' ? `${env.temperature}°C` : '—'}`;
    }
    if (radiationEl) {
        radiationEl.textContent = `Радиация: ${env.radiation ?? '—'} мкЗв`;
    }
    if (windEl) {
        windEl.textContent = `Ветер: ${env.wind ?? '—'} м/с`;
    }
}

function formatSystemValue(key, value) {
    if (typeof value === 'undefined' || value === null || value === '') {
        return '—';
    }
    if (key === 'backupRelayActive') {
        return value ? 'Активен' : 'Не активен';
    }
    if (key === 'clockOffset') {
        return `${value.toFixed ? value.toFixed(2) : value} мкс`;
    }
    if (key === 'nightShift') {
        return value ? 'Включен' : 'Выключен';
    }
    return value;
}

function renderSystems() {
    if (!systemReadouts) return;
    systemReadouts.innerHTML = '';
    const systems = state.systems || {};
    const labels = {
        backupRelayActive: 'Резервный ретранслятор',
        spectrumStatus: 'Сканер спектра',
        loadProfile: 'Профиль нагрузки',
        clockOffset: 'Смещение часов',
        fieldTeam: 'Выездная бригада',
        softwareVersion: 'Версия ПО',
        thermalMode: 'Терморежим',
        lastCalibration: 'Последняя калибровка',
        redundantChannel: 'Резервный канал',
        nightShift: 'Ночной режим'
    };
    Object.entries(labels).forEach(([key, label]) => {
        const value = systems[key];
        const li = document.createElement('li');
        li.innerHTML = `
            <span>${label}</span>
            <span class="value">${formatSystemValue(key, value)}</span>
        `;
        systemReadouts.appendChild(li);
    });
}

function renderAlerts() {
    if (!alertFeed) return;
    alertFeed.innerHTML = '';
    const alerts = state.alerts || [];
    alerts.slice().reverse().forEach(alert => {
        const li = document.createElement('li');
        li.className = `alert ${alert.severity ?? 'info'}`;
        li.innerHTML = `
            <span class="alert-time">${alert.time ?? ''}</span>
            <span class="alert-message">${alert.message}</span>
        `;
        alertFeed.appendChild(li);
    });
}

function formatPercentValue(value) {
    return typeof value === 'number' && !Number.isNaN(value) ? `${Math.round(value)}%` : '—';
}

function formatTemperatureValue(value) {
    return typeof value === 'number' && !Number.isNaN(value) ? `${value.toFixed(1)}°C` : '—';
}

function renderDataCenter() {
    const dc = state.datacenter || {};
    const power = dc.power || {};
    const cooling = dc.cooling || {};
    const virtualization = dc.virtualization || {};
    const operations = dc.operations || {};

    if (dcGridLoadEl) dcGridLoadEl.textContent = formatPercentValue(power.gridLoad);
    if (dcUpsChargeEl) dcUpsChargeEl.textContent = formatPercentValue(power.upsCharge);
    if (dcPduLoadEl) dcPduLoadEl.textContent = formatPercentValue(power.pduLoad);
    if (dcGeneratorEl) dcGeneratorEl.textContent = power.generatorState || '—';
    if (dcCoolingPowerEl) dcCoolingPowerEl.textContent = formatPercentValue(power.coolingPower);

    if (dcSupplyTempEl) dcSupplyTempEl.textContent = formatTemperatureValue(cooling.supplyTemp);
    if (dcReturnTempEl) dcReturnTempEl.textContent = formatTemperatureValue(cooling.returnTemp);
    if (dcHumidityEl) dcHumidityEl.textContent = formatPercentValue(cooling.humidity);
    if (dcAirflowEl) dcAirflowEl.textContent = formatPercentValue(cooling.airflow);
    if (dcFreeCoolingEl) {
        if (typeof cooling.freeCooling === 'boolean') {
            dcFreeCoolingEl.textContent = cooling.freeCooling ? 'Включено' : 'Выключено';
        } else {
            dcFreeCoolingEl.textContent = '—';
        }
    }
    if (dcCoolingStatusEl) dcCoolingStatusEl.textContent = cooling.status || '—';

    if (dcAutomationStatusEl) {
        dcAutomationStatusEl.textContent = virtualization.automation || '—';
    }

    if (dcClusterList) {
        dcClusterList.innerHTML = '';
        const clusters = Array.isArray(virtualization.clusters) ? virtualization.clusters : [];
        if (clusters.length === 0) {
            const placeholder = document.createElement('p');
            placeholder.className = 'empty-state';
            placeholder.textContent = 'Нет активных кластеров.';
            dcClusterList.appendChild(placeholder);
        } else {
            clusters.forEach(cluster => {
                const card = document.createElement('article');
                card.className = 'cluster-card';
                card.innerHTML = `
                    <div class="cluster-header">
                        <strong>${cluster.name}</strong>
                        <span class="cluster-status">${cluster.status || '—'}</span>
                    </div>
                    <div class="cluster-metric">
                        <span>CPU</span>
                        <div class="progress-bar"><span style="width:${Math.round(cluster.cpu ?? 0)}%"></span></div>
                        <span>${formatPercentValue(cluster.cpu)}</span>
                    </div>
                    <div class="cluster-metric">
                        <span>RAM</span>
                        <div class="progress-bar"><span style="width:${Math.round(cluster.memory ?? 0)}%"></span></div>
                        <span>${formatPercentValue(cluster.memory)}</span>
                    </div>
                    <div class="cluster-metric">
                        <span>Storage</span>
                        <div class="progress-bar"><span style="width:${Math.round(cluster.storage ?? 0)}%"></span></div>
                        <span>${formatPercentValue(cluster.storage)}</span>
                    </div>
                `;
                dcClusterList.appendChild(card);
            });
        }
    }

    if (dcRackList) {
        dcRackList.innerHTML = '';
        const racks = Array.isArray(dc.racks) ? dc.racks : [];
        if (racks.length === 0) {
            const placeholder = document.createElement('p');
            placeholder.className = 'empty-state';
            placeholder.textContent = 'Данные по стойкам отсутствуют.';
            dcRackList.appendChild(placeholder);
        } else {
            racks.forEach(rack => {
                const card = document.createElement('article');
                card.className = 'rack-card';
                card.innerHTML = `
                    <header>
                        <strong>${rack.label}</strong>
                        <span>${rack.powerFeed || '—'}</span>
                    </header>
                    <div class="rack-metric">
                        <span>Нагрузка</span>
                        <div class="progress-bar"><span style="width:${Math.round(rack.load ?? 0)}%"></span></div>
                        <span>${formatPercentValue(rack.load)}</span>
                    </div>
                    <div class="rack-meta">
                        <span>Температура: ${formatTemperatureValue(rack.thermal)}</span>
                        <span class="rack-status">${rack.status || '—'}</span>
                    </div>
                `;
                dcRackList.appendChild(card);
            });
        }
    }

    if (dcTicketList) {
        dcTicketList.innerHTML = '';
        const tickets = Array.isArray(operations.tickets) ? operations.tickets : [];
        if (tickets.length === 0) {
            const li = document.createElement('li');
            li.className = 'empty';
            li.textContent = 'Активных задач нет.';
            dcTicketList.appendChild(li);
        } else {
            tickets.forEach(ticket => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <span class="ticket-id">${ticket.id}</span>
                    <span class="ticket-title">${ticket.title}</span>
                    <span class="ticket-status">${ticket.status}</span>
                `;
                dcTicketList.appendChild(li);
            });
        }
    }

    if (dcMaintenanceWindowEl) {
        dcMaintenanceWindowEl.textContent = operations.maintenanceWindow || '—';
    }
    if (dcLastDrillEl) {
        dcLastDrillEl.textContent = operations.lastDrill || '—';
    }

    if (dcAlarmsList) {
        dcAlarmsList.innerHTML = '';
        const alarms = Array.isArray(dc.alarms) ? dc.alarms : [];
        if (!alarms.length) {
            const li = document.createElement('li');
            li.className = 'alert empty';
            li.textContent = 'Нет активных предупреждений.';
            dcAlarmsList.appendChild(li);
        } else {
            alarms.forEach(message => {
                const li = document.createElement('li');
                li.className = 'alert warning';
                li.textContent = message;
                dcAlarmsList.appendChild(li);
            });
        }
    }
}

function formatTimeLabel(timestamp) {
    if (!timestamp) {
        return '';
    }
    return ` · ${timestamp}`;
}

function renderScenarioStates() {
    scenarioRefs.forEach((ref, id) => {
        if (!ref.statusEl) return;
        const info = (state.scenarioStates || {})[id];
        if (!info) {
            ref.statusEl.textContent = 'Не запускалась';
            return;
        }
        ref.statusEl.textContent = `${info.summary}${formatTimeLabel(info.timestamp)}`;
    });
}

function triggerDatacenterAction(action, button) {
    if (!action || !button) return;
    const defaultLabel = button.textContent;
    button.disabled = true;
    button.textContent = 'Выполняется...';
    const formData = new FormData();
    formData.append('operation', action);
    fetch('api.php?action=datacenter-action', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(() => fetchStatus())
        .finally(() => {
            button.disabled = false;
            button.textContent = defaultLabel;
        });
}

function render() {
    renderSatellites();
    renderClients();
    renderLog();
    renderMetrics();
    renderControls();
    renderRadar();
    updateSpaceVisualization();
    renderEnvironment();
    renderSystems();
    renderDataCenter();
    renderAlerts();
    renderScenarioStates();
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

function initScenarioGrid() {
    if (!scenarioGrid) return;
    scenarioGrid.innerHTML = '';
    scenarioDefinitions.forEach(def => {
        const card = document.createElement('article');
        card.className = 'scenario-card';
        card.innerHTML = `
            <div class="scenario-header">
                <h3>${def.title}</h3>
                <p>${def.description}</p>
            </div>
            <div class="scenario-footer">
                <span class="scenario-status" data-scenario="${def.id}">Не запускалась</span>
                <button class="scenario-button" data-scenario="${def.id}">Смоделировать</button>
            </div>
        `;
        scenarioGrid.appendChild(card);
        const button = card.querySelector('button');
        const statusEl = card.querySelector('.scenario-status');
        button.addEventListener('click', () => runScenario(def.id, button));
        scenarioRefs.set(def.id, { card, statusEl, button });
    });
}

dcControlButtons.forEach(button => {
    button.addEventListener('click', () => {
        const action = button.dataset.dcAction;
        triggerDatacenterAction(action, button);
    });
});

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

initScenarioGrid();
setInterval(fetchStatus, 2500);
fetchStatus();
