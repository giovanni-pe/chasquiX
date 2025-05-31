<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChasquiX - Sistema de Taxi</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
   <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
        background: rgb(33, 37, 41);
        color: #f8f9fa;
    }

    .container {
        display: flex;
        height: 100vh;
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        background: #243344;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        overflow-y: auto;
        border-bottom: 1px solid rgba(255, 215, 0, 0.2);
    }

    .map-container {
        flex: 1;
        position: relative;
    }

    #map {
        height: 100%;
        width: 100%;
    }

    .header {
        text-align: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255, 215, 0, 0.3);
    }

    .header h1 {
        color: #ffd700;
        font-size: 22px;
        margin-bottom: 5px;
    }

    .header p {
        color: #adb5bd;
        font-size: 13px;
    }

    .user-type {
        margin-bottom: 15px;
    }

    .user-type label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #ffd700;
        font-size: 14px;
    }

    .user-type select {
        width: 100%;
        padding: 10px;
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 6px;
        font-size: 14px;
        background: #2c3e50;
        color: #f8f9fa;
    }

    .user-info {
        background: rgba(33, 37, 41, 0.7);
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-left: 3px solid #ffd700;
    }

    .connection-status {
        padding: 8px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 12px;
        font-weight: bold;
        font-size: 13px;
    }

    .connected {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid #28a745;
    }

    .disconnected {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
        border: 1px solid #dc3545;
    }

    .request-form {
        background: #2c3e50;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .request-form h3 {
        margin-bottom: 12px;
        color: #ffd700;
        font-size: 16px;
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #adb5bd;
        font-size: 13px;
    }

    .form-group input, .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 5px;
        font-size: 13px;
        background: #243344;
        color: #f8f9fa;
    }

    .btn {
        background: linear-gradient(45deg, #243344, #1e2b38);
        color: #ffd700;
        border: 1px solid rgba(255, 215, 0, 0.3);
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        width: 100%;
        transition: all 0.3s;
        margin-bottom: 8px;
        font-weight: bold;
    }

    .btn:hover {
        background: linear-gradient(45deg, #1e2b38, #243344);
        border-color: rgba(255, 215, 0, 0.5);
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #243344;
    }

    .btn-secondary {
        background: #3a4b5c;
        color: #f8f9fa;
    }

    .btn-danger {
        background: #5c2c3a;
        color: #ff6b6b;
    }

    .active-requests {
        background: #2c3e50;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 15px;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .active-requests h3 {
        color: #ffd700;
        font-size: 15px;
        margin-bottom: 10px;
    }

    .request-item {
        background: rgba(33, 37, 41, 0.7);
        padding: 10px;
        margin-bottom: 8px;
        border-radius: 6px;
        border-left: 3px solid #28a745;
        cursor: pointer;
        transition: all 0.2s;
    }

    .request-item:hover {
        background: rgba(33, 37, 41, 0.9);
    }

    .request-item.driver {
        border-left-color: #007bff;
    }

    .request-item h4 {
        margin-bottom: 5px;
        color: #f8f9fa;
        font-size: 14px;
    }

    .request-item p {
        margin: 2px 0;
        color: #adb5bd;
        font-size: 12px;
    }

    .accept-btn {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid #28a745;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        margin-top: 5px;
        transition: all 0.2s;
    }

    .accept-btn:hover {
        background: rgba(40, 167, 69, 0.4);
    }

    .cancel-btn {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
        border: 1px solid #dc3545;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        margin-top: 5px;
        margin-left: 5px;
        transition: all 0.2s;
    }

    .cancel-btn:hover {
        background: rgba(220, 53, 69, 0.4);
    }

    .coordinates {
        background: #2c3e50;
        padding: 10px;
        border-radius: 5px;
        font-family: monospace;
        font-size: 11px;
        margin-top: 10px;
        color: #adb5bd;
        border: 1px solid rgba(255, 215, 0, 0.1);
    }

    .coordinates strong {
        color: #ffd700;
    }

    .destination-selector {
        background: rgba(0, 123, 255, 0.1);
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 12px;
        border: 1px dashed rgba(0, 123, 255, 0.5);
        text-align: center;
        color: #adb5bd;
        font-size: 13px;
    }

    .destination-selector.active {
        background: rgba(0, 123, 255, 0.2);
        border-style: solid;
        color: #f8f9fa;
    }

    .route-info {
        background: rgba(255, 193, 7, 0.1);
        padding: 8px;
        border-radius: 5px;
        margin-top: 10px;
        font-size: 12px;
        color: #adb5bd;
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .route-info strong {
        color: #ffd700;
    }

    .notification {
        position: fixed;
        top: 15px;
        right: 15px;
        left: 15px;
        padding: 12px 15px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        transform: translateY(-100px);
        transition: transform 0.3s ease;
        font-size: 13px;
    }

    .notification.show {
        transform: translateY(0);
    }

    /* Chat Modal */
    .chat-modal {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        max-height: 70vh;
        background: #243344;
        border-radius: 15px 15px 0 0;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.3);
        z-index: 10001;
        border-top: 2px solid #ffd700;
    }

    .chat-modal.active {
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        background: #2c3e50;
        color: #ffd700;
        padding: 12px 15px;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-header h3 {
        margin: 0;
        font-size: 16px;
    }

    .chat-close {
        background: none;
        border: none;
        color: #ffd700;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background: #243344;
    }

    .chat-message {
        margin-bottom: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        max-width: 80%;
        font-size: 13px;
    }

    .chat-message.sent {
        background: rgba(0, 123, 255, 0.3);
        color: white;
        margin-left: auto;
        text-align: right;
        border: 1px solid rgba(0, 123, 255, 0.5);
    }

    .chat-message.received {
        background: rgba(33, 37, 41, 0.7);
        color: #f8f9fa;
        border: 1px solid rgba(255, 215, 0, 0.2);
    }

    .chat-message .time {
        font-size: 10px;
        opacity: 0.7;
        margin-top: 4px;
        color: #adb5bd;
    }

    .chat-input-container {
        padding: 12px;
        border-top: 1px solid rgba(255, 215, 0, 0.1);
        display: flex;
        gap: 8px;
        background: #2c3e50;
    }

    .chat-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 20px;
        font-size: 13px;
        background: #243344;
        color: #f8f9fa;
    }

    .chat-send {
        background: rgba(0, 123, 255, 0.3);
        color: #f8f9fa;
        border: 1px solid rgba(0, 123, 255, 0.5);
        padding: 8px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }

    .chat-send:hover {
        background: rgba(0, 123, 255, 0.5);
    }

    .chat-icon {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
        border: 1px solid #28a745;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        margin-left: 5px;
        transition: all 0.2s;
    }

    .chat-icon:hover {
        background: rgba(40, 167, 69, 0.4);
    }

    .modal-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 10000;
    }

    .modal-backdrop.active {
        display: block;
    }

    .route-preview {
        background: rgba(0, 123, 255, 0.1);
        padding: 8px;
        border-radius: 5px;
        margin: 8px 0;
        font-size: 12px;
        color: #adb5bd;
        border: 1px solid rgba(0, 123, 255, 0.2);
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Estilos para los iconos del mapa */
    .custom-div-icon {
        background: none;
        border: none;
    }

    .marker-pin {
        width: 26px;
        height: 26px;
        border-radius: 50% 50% 50% 0;
        position: absolute;
        transform: rotate(-45deg);
        left: 50%;
        top: 50%;
        margin: -13px 0 0 -13px;
    }

    .marker-pin::after {
        content: '';
        width: 12px;
        height: 12px;
        margin: 7px 0 0 7px;
        background: #fff;
        position: absolute;
        border-radius: 50%;
    }

    .client-marker {
        background-color: #28a745;
        box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.5);
    }

    .driver-marker {
        background-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5);
    }

    .destination-marker {
        background-color: #ff6347;
        box-shadow: 0 0 0 2px rgba(255, 99, 71, 0.5);
    }

    /* Ocultar controles de ruta de Leaflet Routing Machine */
    .leaflet-routing-container {
        display: none;
    }

    .distance-badge {
        background: rgba(0, 123, 255, 0.3);
        color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 5px;
        border: 1px solid rgba(0, 123, 255, 0.5);
    }

    .btn-group {
        display: flex;
        gap: 8px;
        margin-top: 8px;
    }

    .btn-group .btn {
        flex: 1;
        margin-bottom: 0;
        padding: 8px 12px;
    }

    /* Mejoras para móvil */
    @media (min-width: 768px) {
        .container {
            flex-direction: row;
        }

        .sidebar {
            width: 350px;
            height: 100vh;
            border-right: 1px solid rgba(255, 215, 0, 0.2);
            border-bottom: none;
        }

        .chat-modal {
            bottom: auto;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            max-height: 70vh;
            border-radius: 10px;
        }

        .notification {
            left: auto;
            max-width: 300px;
        }
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(255, 215, 0, 0.1);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(255, 215, 0, 0.3);
        border-radius: 3px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 215, 0, 0.5);
    }
</style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="header">
                <h1>🚕 ChasquiX</h1>
                <p>Sistema de Taxi - Tingo María</p>
            </div>

            <div id="connectionStatus" class="connection-status disconnected">
                🔴 Desconectado
            </div>

            <div class="user-type">
                <label for="userTypeSelect">Tipo de Usuario:</label>
                <select id="userTypeSelect">
                    <option value="client">🙋‍♂️ Cliente (Solicitar Taxi)</option>
                    <option value="driver">🏍️ Mototaxista (Ofrecer Servicio)</option>
                </select>
            </div>

            <div class="user-info">
                <div id="userInfo">
                    <p><strong>Usuario:</strong> <span id="userId"></span></p>
                    <p><strong>Estado:</strong> <span id="userStatus">Esperando...</span></p>
                </div>
            </div>

            <div class="request-form">
                <h3 id="formTitle">Solicitar Taxi</h3>

                <div id="destinationSelector" class="destination-selector">
                    <p>📍 <strong>Click en el mapa para seleccionar destino</strong></p>
                </div>

                <div class="form-group">
                    <label for="destination">Destino:</label>
                    <textarea id="destination" rows="2" placeholder="Describe tu destino en Tingo María"></textarea>
                </div>

                <div class="form-group">
                    <label for="description">Descripción adicional:</label>
                    <textarea id="description" rows="2" placeholder="Ej: Frente al mercado central, llevar equipaje..."></textarea>
                </div>

                <div class="form-group" id="priceGroup" style="display: none;">
                    <label for="price">Precio ofrecido (S/):</label>
                    <input type="number" id="price" placeholder="15.00" step="0.50" min="5">
                </div>

                <div id="routeInfo" class="route-info" style="display: none;">
                    <strong>Información de ruta:</strong>
                    <p>Distancia: <span id="routeDistance">-</span></p>
                    <p>Tiempo estimado: <span id="routeDuration">-</span></p>
                </div>

                <button id="actionBtn" class="btn">📍 Compartir Mi Ubicación</button>
                <button id="selectDestinationBtn" class="btn btn-secondary" style="display: none;">🎯 Seleccionar Destino</button>
                <div class="btn-group" id="confirmButtons" style="display: none;">
                    <button id="confirmRequestBtn" class="btn">✅ Confirmar Solicitud</button>
                    <button id="clearRouteBtn" class="btn btn-danger">🗑️ Limpiar</button>
                </div>
                <button id="cancelBtn" class="btn btn-danger" style="display: none;">❌ Cancelar Solicitud</button>
                <button id="openChatBtn" class="btn btn-chat" style="display: none;">
                    💬 Chat con <span id="chatPartnerType">-</span>
                    <span id="unreadIndicator" class="chat-indicator" style="display: none;">0</span>
                </button>
            </div>

            <div class="active-requests">
                <h3>🗺️ Actividad en Tiempo Real</h3>
                <div id="requestsList"></div>
            </div>

            <div class="coordinates" id="coordinates">
                <strong>📍 Mi ubicación:</strong><br>
                Latitud: <span id="lat">-</span><br>
                Longitud: <span id="lng">-</span>
            </div>
        </div>

        <div class="map-container">
            <div id="map"></div>
        </div>
    </div>

    <!-- Chat Container -->
    <div id="chatContainer" class="chat-container">
        <div class="chat-header">
            <h4>💬 Chat con <span id="chatPartnerName">-</span></h4>
            <button class="chat-close" onclick="closeChat()">×</button>
        </div>
        <div id="chatMessages" class="chat-messages"></div>
        <div class="chat-input-container">
            <input type="text" id="chatInput" class="chat-input" placeholder="Escribe un mensaje..." onkeypress="handleChatKeyPress(event)">
            <button class="chat-send" onclick="sendChatMessage()">➤</button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.min.js"></script>
    <script>
        // Configuración global
        const MQTT_CONFIG = {
            host: 'localhost',
            port: 9001,
            topics: {
                clients: 'chasquix/clients',
                drivers: 'chasquix/drivers',
                routes: 'chasquix/routes',
                messages: 'chasquix/messages',
                assignments: 'chasquix/assignments'
            }
        };

        // Variables globales
        let map;
        let mqttClient;
        let currentUser = {
            id: 'user_' + Date.now(),
            type: 'client',
            lat: null,
            lng: null,
            status: 'waiting'
        };
        let markers = new Map();
        let routeControl = null;
        let currentRequest = null;
        let destinationMarker = null;
        let destinationCoords = null;
        let selectingDestination = false;
        let activeAssignment = null;
        let chatMessages = [];
        let chatPartner = null;
        let unreadMessages = 0;

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            initMQTT();
            initEventListeners();
            getUserLocation();
            updateUI();
        });

        // Inicializar el mapa
        function initMap() {
            // Coordenadas de Tingo María, Perú
            const tingoMaria = [-9.2945, -76.0073];

            map = L.map('map').setView(tingoMaria, 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Evento para hacer clic en el mapa
            map.on('click', function(e) {
                if (selectingDestination) {
                    setDestination(e.latlng.lat, e.latlng.lng);
                }
            });
        }

        // Inicializar MQTT
        function initMQTT() {
            mqttClient = new Paho.MQTT.Client(MQTT_CONFIG.host, MQTT_CONFIG.port, currentUser.id);

            mqttClient.onConnectionLost = function(response) {
                console.log('Conexión MQTT perdida:', response);
                updateConnectionStatus(false);
                setTimeout(initMQTT, 5000); // Reintentar conexión
            };

            mqttClient.onMessageArrived = function(message) {
                handleMQTTMessage(message);
            };

            // Conectar al broker MQTT
            mqttClient.connect({
                onSuccess: function() {
                    console.log('Conectado a MQTT');
                    updateConnectionStatus(true);
                    subscribeToTopics();
                },
                onFailure: function(error) {
                    console.error('Error conectando a MQTT:', error);
                    updateConnectionStatus(false);
                    setTimeout(initMQTT, 5000); // Reintentar
                }
            });
        }

        // Suscribirse a los topics MQTT
        function subscribeToTopics() {
            Object.values(MQTT_CONFIG.topics).forEach(topic => {
                mqttClient.subscribe(topic);
                console.log('Suscrito a:', topic);
            });
        }

        // Manejar mensajes MQTT
        function handleMQTTMessage(message) {
            try {
                const data = JSON.parse(message.payloadString);
                const topic = message.destinationName;

                console.log('Mensaje recibido:', topic, data);

                switch (topic) {
                    case MQTT_CONFIG.topics.clients:
                        handleClientMessage(data);
                        break;
                    case MQTT_CONFIG.topics.drivers:
                        handleDriverMessage(data);
                        break;
                    case MQTT_CONFIG.topics.routes:
                        handleRouteMessage(data);
                        break;
                    case MQTT_CONFIG.topics.messages:
                        handleDirectMessage(data);
                        break;
                    case MQTT_CONFIG.topics.assignments:
                        handleAssignmentMessage(data);
                        break;
                }
            } catch (error) {
                console.error('Error procesando mensaje MQTT:', error);
            }
        }

        // Manejar mensajes de clientes
        function handleClientMessage(data) {
            if (data.action === 'request') {
                if (data.userId !== currentUser.id) {
                    addOrUpdateMarker(data, 'client');
                    addToRequestsList(data, 'client');
                }
            } else if (data.action === 'cancel') {
                removeMarker(data.userId);
                removeFromRequestsList(data.userId);
            }
        }

        // Manejar mensajes de conductores
        function handleDriverMessage(data) {
            if (data.action === 'available') {
                if (data.userId !== currentUser.id) {
                    addOrUpdateMarker(data, 'driver');
                    addToRequestsList(data, 'driver');
                }
            } else if (data.action === 'accept') {
                handleDriverAcceptance(data);
            } else if (data.action === 'offline') {
                removeMarker(data.userId);
                removeFromRequestsList(data.userId);
            }
        }

        // Manejar mensajes de asignación
        function handleAssignmentMessage(data) {
            if (data.clientId === currentUser.id || data.driverId === currentUser.id) {
                activeAssignment = data;

                if (data.action === 'assigned') {
                    handleAssignment(data);
                } else if (data.action === 'completed') {
                    handleTripCompletion(data);
                }
            }
        }

        // Manejar asignación de viaje
        function handleAssignment(data) {
            if (data.clientId === currentUser.id) {
                // Cliente: ocultar otros taxistas
                markers.forEach((marker, markerId) => {
                    if (markerId !== data.driverId && markerId !== currentUser.id) {
                        map.removeLayer(marker);
                    }
                });

                showNotification(`¡Taxista ${data.driverId} ha aceptado tu solicitud!`, 'success');
                currentUser.status = 'in_trip';

                // Establecer chat con el conductor
                chatPartner = {
                    id: data.driverId,
                    type: 'driver'
                };
                updateChatButton();
                addSystemMessage('Ahora puedes chatear con tu conductor');

                // Mostrar solo el taxista asignado
                if (data.driverLocation) {
                    addOrUpdateMarker({
                        userId: data.driverId,
                        lat: data.driverLocation.lat,
                        lng: data.driverLocation.lng,
                        status: 'assigned'
                    }, 'driver');
                }
            } else if (data.driverId === currentUser.id) {
                // Conductor: mostrar ruta al cliente
                showNotification(`¡Nueva carrera asignada!`, 'success');
                currentUser.status = 'in_trip';

                // Establecer chat con el cliente
                chatPartner = {
                    id: data.clientId,
                    type: 'client'
                };
                updateChatButton();
                addSystemMessage('Ahora puedes chatear con tu pasajero');

                if (data.clientLocation && data.destination) {
                    drawAssignedRoute(data.clientLocation, data.destination);
                }
            }

            updateUI();
        }
        data.driverId && markerId !== currentUser.id) {
            map.removeLayer(marker);
        }
        });

        showNotification(`¡Taxista ${data.driverId} ha aceptado tu solicitud!`, 'success');
        currentUser.status = 'in_trip';

        // Mostrar solo el taxista asignado
        if (data.driverLocation) {
            addOrUpdateMarker({
                userId: data.driverId,
                lat: data.driverLocation.lat,
                lng: data.driverLocation.lng,
                status: 'assigned'
            }, 'driver');
        }
        } else if (data.driverId === currentUser.id) {
            // Conductor: mostrar ruta al cliente
            showNotification(`¡Nueva carrera asignada!`, 'success');
            currentUser.status = 'in_trip';

            if (data.clientLocation && data.destination) {
                drawAssignedRoute(data.clientLocation, data.destination);
            }
        }

        updateUI();
        }

        // Inicializar event listeners
        function initEventListeners() {
            document.getElementById('userTypeSelect').addEventListener('change', function() {
                currentUser.type = this.value;
                updateUI();
            });

            document.getElementById('actionBtn').addEventListener('click', function() {
                if (currentUser.type === 'client') {
                    getUserLocation();
                } else {
                    sendDriverAvailability();
                }
            });

            document.getElementById('selectDestinationBtn').addEventListener('click', function() {
                startDestinationSelection();
            });

            document.getElementById('confirmRequestBtn').addEventListener('click', function() {
                confirmAndSendRequest();
            });

            document.getElementById('clearRouteBtn').addEventListener('click', function() {
                clearRoute();
            });

            document.getElementById('cancelBtn').addEventListener('click', function() {
                cancelCurrentRequest();
            });

            // Click en elementos de la lista
            document.getElementById('requestsList').addEventListener('click', function(e) {
                const requestItem = e.target.closest('.request-item');
                if (requestItem && e.target.classList.contains('accept-btn')) {
                    const data = JSON.parse(requestItem.dataset.requestData);
                    acceptRequest(data.userId, data);
                }
            });
        }

        // Obtener ubicación del usuario
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        updateLocation(position.coords.latitude, position.coords.longitude);

                        if (currentUser.type === 'client') {
                            showNotification('Ubicación actualizada. Ahora selecciona tu destino.', 'info');
                            document.getElementById('selectDestinationBtn').style.display = 'block';
                        }
                    },
                    function(error) {
                        console.error('Error obteniendo ubicación:', error);
                        // Usar ubicación por defecto en Tingo María
                        updateLocation(-9.2945, -76.0073);
                    }
                );
            } else {
                updateLocation(-9.2945, -76.0073);
            }
        }

        // Actualizar ubicación
        function updateLocation(lat, lng) {
            currentUser.lat = lat;
            currentUser.lng = lng;

            document.getElementById('lat').textContent = lat.toFixed(6);
            document.getElementById('lng').textContent = lng.toFixed(6);

            // Centrar mapa en la nueva ubicación
            map.setView([lat, lng], 16);

            // Agregar/actualizar marcador del usuario actual
            addOrUpdateMarker(currentUser, currentUser.type);
        }

        // Iniciar selección de destino
        function startDestinationSelection() {
            selectingDestination = true;
            document.getElementById('destinationSelector').classList.add('active');
            document.getElementById('destinationSelector').innerHTML =
                '<p>🎯 <strong>Haz click en el mapa para marcar tu destino</strong></p>';

            map.getContainer().style.cursor = 'crosshair';
            showNotification('Haz click en el mapa para seleccionar tu destino', 'info');
        }

        // Establecer destino
        function setDestination(lat, lng) {
            destinationCoords = {
                lat,
                lng
            };

            // Remover marcador anterior si existe
            if (destinationMarker) {
                map.removeLayer(destinationMarker);
            }

            // Crear marcador de destino
            const iconHtml = '<div class="marker-pin destination-marker"></div>';
            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });

            destinationMarker = L.marker([lat, lng], {
                icon: customIcon
            }).addTo(map);
            destinationMarker.bindPopup('<strong>🎯 Tu destino</strong>').openPopup();

            // Dibujar ruta
            drawRoute();

            // Actualizar UI
            selectingDestination = false;
            map.getContainer().style.cursor = '';
            document.getElementById('destinationSelector').classList.remove('active');
            document.getElementById('destinationSelector').innerHTML =
                '<p>✅ <strong>Destino seleccionado</strong></p>';

            document.getElementById('confirmButtons').style.display = 'block';
            document.getElementById('selectDestinationBtn').style.display = 'none';
        }

        // Dibujar ruta
        function drawRoute() {
            // Remover ruta anterior si existe
            if (routeControl) {
                map.removeControl(routeControl);
            }

            routeControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentUser.lat, currentUser.lng),
                    L.latLng(destinationCoords.lat, destinationCoords.lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() {
                    return null;
                }, // No crear marcadores adicionales
                lineOptions: {
                    styles: [{
                        color: '#764ba2',
                        weight: 4,
                        opacity: 0.7
                    }]
                }
            }).on('routesfound', function(e) {
                const routes = e.routes;
                const summary = routes[0].summary;

                // Actualizar información de ruta
                document.getElementById('routeInfo').style.display = 'block';
                document.getElementById('routeDistance').textContent =
                    (summary.totalDistance / 1000).toFixed(2) + ' km';
                document.getElementById('routeDuration').textContent =
                    Math.round(summary.totalTime / 60) + ' min';

                // Ajustar vista del mapa
                const bounds = L.latLngBounds([
                    [currentUser.lat, currentUser.lng],
                    [destinationCoords.lat, destinationCoords.lng]
                ]);
                map.fitBounds(bounds, {
                    padding: [50, 50]
                });
            }).addTo(map);
        }

        // Confirmar y enviar solicitud
        function confirmAndSendRequest() {
            if (!currentUser.lat || !currentUser.lng || !destinationCoords) {
                alert('Por favor, selecciona tu ubicación y destino');
                return;
            }

            const destination = document.getElementById('destination').value.trim();
            const description = document.getElementById('description').value.trim();

            const request = {
                userId: currentUser.id,
                action: 'request',
                lat: currentUser.lat,
                lng: currentUser.lng,
                destinationLat: destinationCoords.lat,
                destinationLng: destinationCoords.lng,
                destination: destination || 'Destino seleccionado en el mapa',
                description: description,
                timestamp: Date.now(),
                status: 'waiting'
            };

            publishMessage(MQTT_CONFIG.topics.clients, request);
            currentRequest = request;
            currentUser.status = 'requesting';
            updateUI();

            showNotification('Solicitud de taxi enviada. Buscando conductores cercanos...', 'success');

            // Mostrar taxistas cercanos
            highlightNearbyDrivers();
        }

        // Resaltar conductores cercanos
        function highlightNearbyDrivers() {
            const maxDistance = 2; // km

            markers.forEach((marker, markerId) => {
                const markerData = marker.options.userData;
                if (markerData && markerData.type === 'driver') {
                    const distance = calculateDistance(
                        currentUser.lat, currentUser.lng,
                        markerData.lat, markerData.lng
                    );

                    if (distance <= maxDistance) {
                        marker.setIcon(createPulsingIcon('driver'));

                        // Notificar al conductor
                        const notification = {
                            targetId: markerId,
                            senderId: currentUser.id,
                            message: `Nueva solicitud de taxi cerca (${distance.toFixed(1)} km)`,
                            type: 'nearby_request',
                            data: currentRequest
                        };
                        publishMessage(MQTT_CONFIG.topics.messages, notification);
                    }
                }
            });
        }

        // Crear icono con efecto de pulso
        function createPulsingIcon(type) {
            const iconClass = type === 'client' ? 'client-marker' : 'driver-marker';
            const iconHtml = `<div class="marker-pin ${iconClass} pulse"></div>`;

            return L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });
        }

        // Limpiar ruta
        function clearRoute() {
            if (routeControl) {
                map.removeControl(routeControl);
                routeControl = null;
            }

            if (destinationMarker) {
                map.removeLayer(destinationMarker);
                destinationMarker = null;
            }

            destinationCoords = null;
            document.getElementById('routeInfo').style.display = 'none';
            document.getElementById('confirmButtons').style.display = 'none';
            document.getElementById('selectDestinationBtn').style.display = 'block';
            document.getElementById('destinationSelector').innerHTML =
                '<p>📍 <strong>Click en el mapa para seleccionar destino</strong></p>';
        }

        // Enviar disponibilidad del conductor
        function sendDriverAvailability() {
            if (!currentUser.lat || !currentUser.lng) {
                alert('Por favor, permite el acceso a tu ubicación');
                return;
            }

            const description = document.getElementById('description').value.trim();
            const price = document.getElementById('price').value;

            const availability = {
                userId: currentUser.id,
                action: 'available',
                lat: currentUser.lat,
                lng: currentUser.lng,
                description: description || 'Mototaxi disponible',
                price: price || '10.00',
                timestamp: Date.now(),
                status: 'available'
            };

            publishMessage(MQTT_CONFIG.topics.drivers, availability);
            currentRequest = availability;
            currentUser.status = 'available';
            updateUI();

            showNotification('Ahora estás disponible para servicios', 'success');
        }

        // Cancelar solicitud actual
        function cancelCurrentRequest() {
            if (currentRequest) {
                const cancelMessage = {
                    userId: currentUser.id,
                    action: 'cancel',
                    timestamp: Date.now()
                };

                const topic = currentUser.type === 'client' ?
                    MQTT_CONFIG.topics.clients : MQTT_CONFIG.topics.drivers;

                publishMessage(topic, cancelMessage);

                currentRequest = null;
                currentUser.status = 'waiting';
                clearRoute();
                updateUI();

                showNotification('Solicitud cancelada', 'info');
            }
        }

        // Aceptar solicitud (para conductores)
        function acceptRequest(requestId, clientData) {
            const acceptance = {
                clientId: requestId,
                driverId: currentUser.id,
                action: 'assigned',
                driverLocation: {
                    lat: currentUser.lat,
                    lng: currentUser.lng
                },
                clientLocation: {
                    lat: clientData.lat,
                    lng: clientData.lng
                },
                destination: clientData.destinationLat ? {
                    lat: clientData.destinationLat,
                    lng: clientData.destinationLng
                } : null,
                timestamp: Date.now()
            };

            publishMessage(MQTT_CONFIG.topics.assignments, acceptance);

            // Notificar al cliente
            const notification = {
                targetId: requestId,
                senderId: currentUser.id,
                message: `Conductor ${currentUser.id} ha aceptado tu solicitud`,
                type: 'acceptance'
            };
            publishMessage(MQTT_CONFIG.topics.messages, notification);

            currentUser.status = 'in_trip';
            updateUI();
            showNotification('Solicitud aceptada. Dirigiéndose al cliente...', 'success');
        }

        // Dibujar ruta asignada (para conductor)
        function drawAssignedRoute(clientLocation, destination) {
            if (routeControl) {
                map.removeControl(routeControl);
            }

            const waypoints = [
                L.latLng(currentUser.lat, currentUser.lng),
                L.latLng(clientLocation.lat, clientLocation.lng)
            ];

            if (destination) {
                waypoints.push(L.latLng(destination.lat, destination.lng));
            }

            routeControl = L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function() {
                    return null;
                },
                lineOptions: {
                    styles: [{
                        color: '#28a745',
                        weight: 5,
                        opacity: 0.7
                    }]
                }
            }).on('routesfound', function(e) {
                const bounds = L.latLngBounds(waypoints);
                map.fitBounds(bounds, {
                    padding: [50, 50]
                });
            }).addTo(map);
        }

        // Publicar mensaje MQTT
        function publishMessage(topic, data) {
            if (mqttClient && mqttClient.isConnected()) {
                const message = new Paho.MQTT.Message(JSON.stringify(data));
                message.destinationName = topic;
                mqttClient.send(message);
                console.log('Mensaje enviado a', topic, data);
            } else {
                console.error('Cliente MQTT no conectado');
                showNotification('Error de conexión. Reintentando...', 'error');
            }
        }

        // Agregar o actualizar marcador en el mapa
        function addOrUpdateMarker(data, type) {
            const markerId = data.userId;

            // Remover marcador existente si existe
            if (markers.has(markerId)) {
                map.removeLayer(markers.get(markerId));
            }

            // Crear icono personalizado
            const iconClass = type === 'client' ? 'client-marker' : 'driver-marker';
            const iconHtml = `<div class="marker-pin ${iconClass}"></div>`;

            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });

            // Crear marcador
            const marker = L.marker([data.lat, data.lng], {
                icon: customIcon,
                userData: {
                    ...data,
                    type
                }
            }).addTo(map);

            // Crear popup con botón de contacto
            let popupContent = `
                <div style="min-width: 200px;">
                    <h4>${type === 'client' ? '🙋‍♂️ Cliente' : '🏍️ Mototaxista'}</h4>
                    <p><strong>ID:</strong> ${data.userId}</p>
                    <p><strong>Hora:</strong> ${new Date(data.timestamp).toLocaleTimeString()}</p>
            `;

            if (type === 'client' && data.destination) {
                popupContent += `<p><strong>Destino:</strong> ${data.destination}</p>`;
            }

            if (data.description) {
                popupContent += `<p><strong>Descripción:</strong> ${data.description}</p>`;
            }

            if (type === 'driver' && data.price) {
                popupContent += `<p><strong>Precio:</strong> S/ ${data.price}</p>`;
            }

            // Botón de contacto para todos los usuarios excepto uno mismo
            if (currentUser.id !== data.userId) {
                popupContent += `
                    <button onclick="initiateContact('${data.userId}', '${type}')" class="contact-btn">
                        💬 Contactar
                    </button>
                `;
            }

            popupContent += '</div>';
            marker.bindPopup(popupContent);

            // Guardar referencia del marcador
            markers.set(markerId, marker);

            // Agregar efecto de pulso si es el usuario actual
            if (markerId === currentUser.id) {
                marker.getElement().classList.add('pulse');
            }
        }

        // Remover marcador
        function removeMarker(markerId) {
            if (markers.has(markerId)) {
                map.removeLayer(markers.get(markerId));
                markers.delete(markerId);
            }
        }

        // Agregar a lista de solicitudes
        function addToRequestsList(data, type) {
            const requestsList = document.getElementById('requestsList');
            const existingItem = document.getElementById(`request_${data.userId}`);

            if (existingItem) {
                existingItem.remove();
            }

            const distance = calculateDistance(
                currentUser.lat, currentUser.lng,
                data.lat, data.lng
            );

            const requestItem = document.createElement('div');
            requestItem.className = `request-item ${type}`;
            requestItem.id = `request_${data.userId}`;
            requestItem.dataset.requestData = JSON.stringify(data);

            let content = `
                <h4>${type === 'client' ? '🙋‍♂️ Cliente' : '🏍️ Mototaxista'}
                    <span class="distance-badge">${distance.toFixed(1)} km</span>
                </h4>
                <p>📍 ${data.lat.toFixed(4)}, ${data.lng.toFixed(4)}</p>
                <p>🕒 ${new Date(data.timestamp).toLocaleTimeString()}</p>
            `;

            if (type === 'client' && data.destination) {
                content += `<p>🎯 ${data.destination}</p>`;
            }

            if (data.description) {
                content += `<p>💬 ${data.description}</p>`;
            }

            if (type === 'driver' && data.price) {
                content += `<p>💰 S/ ${data.price}</p>`;
            }

            // Botones de acción
            if (currentUser.type !== type && currentUser.id !== data.userId && currentUser.status === 'waiting') {
                content += `
                    <button class="accept-btn">
                        ${type === 'client' ? 'Aceptar' : 'Solicitar'}
                    </button>
                    <button class="contact-btn" onclick="initiateContact('${data.userId}', '${type}')">
                        💬 Contactar
                    </button>
                `;
            }

            requestItem.innerHTML = content;

            // Insertar ordenado por distancia
            const items = requestsList.querySelectorAll('.request-item');
            let inserted = false;

            for (let item of items) {
                const itemData = JSON.parse(item.dataset.requestData);
                const itemDistance = calculateDistance(
                    currentUser.lat, currentUser.lng,
                    itemData.lat, itemData.lng
                );

                if (distance < itemDistance) {
                    requestsList.insertBefore(requestItem, item);
                    inserted = true;
                    break;
                }
            }

            if (!inserted) {
                requestsList.appendChild(requestItem);
            }
        }

        // Remover de lista de solicitudes
        function removeFromRequestsList(userId) {
            const item = document.getElementById(`request_${userId}`);
            if (item) {
                item.remove();
            }
        }

        // Manejar aceptación del conductor
        function handleDriverAcceptance(data) {
            if (data.targetId === currentUser.id) {
                showNotification('¡Un conductor aceptó tu solicitud!', 'success');
                currentUser.status = 'matched';
                updateUI();
            }
        }

        // Manejar mensajes de rutas
        function handleRouteMessage(data) {
            // Implementar actualización de rutas en tiempo real si es necesario
        }

        // Manejar mensajes directos
        function handleDirectMessage(data) {
            if (data.targetId === currentUser.id) {
                if (data.type === 'chat') {
                    // Mensaje de chat
                    handleChatMessage(data);
                } else {
                    // Otros tipos de mensajes
                    showNotification(data.message, data.type || 'info');

                    // Si es una solicitud cercana, resaltar en el mapa
                    if (data.type === 'nearby_request' && data.data) {
                        highlightRequest(data.data);
                    }

                    // Si es una solicitud de contacto
                    if (data.type === 'contact_request') {
                        handleContactRequest(data);
                    }
                }
            }
        }

        // Iniciar contacto con otro usuario
        window.initiateContact = function(userId, userType) {
            const targetUser = {
                id: userId,
                type: userType
            };

            // Verificar si ya hay un chat activo
            if (chatPartner && chatPartner.id === userId) {
                openChat();
                return;
            }

            // Establecer nuevo chat
            chatPartner = targetUser;
            chatMessages = [];

            // Enviar solicitud de contacto
            const contactRequest = {
                targetId: userId,
                senderId: currentUser.id,
                senderType: currentUser.type,
                message: `${currentUser.type === 'client' ? 'Cliente' : 'Taxista'} ${currentUser.id} quiere contactarte`,
                type: 'contact_request',
                timestamp: Date.now()
            };

            publishMessage(MQTT_CONFIG.topics.messages, contactRequest);

            // Abrir chat
            openChat();

            // Agregar mensaje de sistema
            addSystemMessage('Chat iniciado. Puedes enviar mensajes al ' +
                (userType === 'client' ? 'cliente' : 'taxista'));
        };

        // Manejar solicitud de contacto
        function handleContactRequest(data) {
            if (!chatPartner || chatPartner.id !== data.senderId) {
                chatPartner = {
                    id: data.senderId,
                    type: data.senderType
                };
                chatMessages = [];
            }

            // Mostrar botón de chat si no está visible
            updateChatButton();

            // Incrementar contador de mensajes no leídos
            if (!document.getElementById('chatContainer').classList.contains('active')) {
                unreadMessages++;
                updateUnreadIndicator();
            }
        }

        // Manejar mensaje de chat
        function handleChatMessage(data) {
            if (data.senderId === chatPartner?.id) {
                const message = {
                    id: Date.now(),
                    senderId: data.senderId,
                    text: data.text,
                    timestamp: data.timestamp,
                    type: 'received'
                };

                chatMessages.push(message);

                if (document.getElementById('chatContainer').classList.contains('active')) {
                    displayMessage(message);
                    scrollToBottom();
                } else {
                    unreadMessages++;
                    updateUnreadIndicator();
                }

                // Sonido de notificación
                playNotificationSound();
            }
        }

        // Abrir chat
        window.openChat = function() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.classList.add('active');

            // Actualizar nombre del chat
            const partnerType = chatPartner.type === 'client' ? 'Cliente' : 'Taxista';
            document.getElementById('chatPartnerName').textContent = `${partnerType} ${chatPartner.id}`;

            // Limpiar mensajes no leídos
            unreadMessages = 0;
            updateUnreadIndicator();

            // Mostrar mensajes existentes
            displayAllMessages();

            // Focus en input
            document.getElementById('chatInput').focus();
        };

        // Cerrar chat
        window.closeChat = function() {
            document.getElementById('chatContainer').classList.remove('active');
        };

        // Enviar mensaje de chat
        window.sendChatMessage = function() {
            const input = document.getElementById('chatInput');
            const text = input.value.trim();

            if (!text || !chatPartner) return;

            const message = {
                id: Date.now(),
                senderId: currentUser.id,
                text: text,
                timestamp: Date.now(),
                type: 'sent'
            };

            // Agregar a mensajes locales
            chatMessages.push(message);
            displayMessage(message);
            scrollToBottom();

            // Enviar por MQTT
            const chatData = {
                targetId: chatPartner.id,
                senderId: currentUser.id,
                text: text,
                type: 'chat',
                timestamp: message.timestamp
            };

            publishMessage(MQTT_CONFIG.topics.messages, chatData);

            // Limpiar input
            input.value = '';
        };

        // Manejar tecla Enter en chat
        window.handleChatKeyPress = function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendChatMessage();
            }
        };

        // Mostrar mensaje en el chat
        function displayMessage(message) {
            const messagesContainer = document.getElementById('chatMessages');

            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${message.type}`;

            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = `message-bubble ${message.type}`;

            const textP = document.createElement('p');
            textP.style.margin = '0';
            textP.textContent = message.text;

            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = new Date(message.timestamp).toLocaleTimeString();

            bubbleDiv.appendChild(textP);
            bubbleDiv.appendChild(timeDiv);
            messageDiv.appendChild(bubbleDiv);

            messagesContainer.appendChild(messageDiv);
        }

        // Mostrar todos los mensajes
        function displayAllMessages() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.innerHTML = '';

            chatMessages.forEach(message => {
                displayMessage(message);
            });

            scrollToBottom();
        }

        // Agregar mensaje del sistema
        function addSystemMessage(text) {
            const message = {
                id: Date.now(),
                senderId: 'system',
                text: text,
                timestamp: Date.now(),
                type: 'received'
            };

            chatMessages.push(message);

            if (document.getElementById('chatContainer').classList.contains('active')) {
                displayMessage(message);
                scrollToBottom();
            }
        }

        // Scroll al final del chat
        function scrollToBottom() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Actualizar botón de chat
        function updateChatButton() {
            const chatBtn = document.getElementById('openChatBtn');
            const chatPartnerType = document.getElementById('chatPartnerType');

            if (chatPartner) {
                chatBtn.style.display = 'block';
                chatPartnerType.textContent = chatPartner.type === 'client' ? 'Cliente' : 'Taxista';
            } else {
                chatBtn.style.display = 'none';
            }
        }

        // Actualizar indicador de mensajes no leídos
        function updateUnreadIndicator() {
            const indicator = document.getElementById('unreadIndicator');

            if (unreadMessages > 0) {
                indicator.style.display = 'flex';
                indicator.textContent = unreadMessages > 9 ? '9+' : unreadMessages;
            } else {
                indicator.style.display = 'none';
            }
        }

        // Reproducir sonido de notificación
        function playNotificationSound() {
            // Crear un simple beep usando Web Audio API
            try {
                const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                gainNode.gain.value = 0.1;

                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {
                console.log('No se pudo reproducir sonido:', e);
            }
        }

        // Resaltar solicitud en el mapa
        function highlightRequest(requestData) {
            const marker = markers.get(requestData.userId);
            if (marker) {
                marker.openPopup();
                // Agregar efecto visual temporal
                const element = marker.getElement();
                if (element) {
                    element.classList.add('pulse');
                    setTimeout(() => {
                        element.classList.remove('pulse');
                    }, 5000);
                }
            }
        }

        // Manejar finalización de viaje
        function handleTripCompletion(data) {
            activeAssignment = null;
            currentUser.status = 'waiting';
            currentRequest = null;

            // Limpiar mapa
            clearRoute();

            // Recargar marcadores
            markers.forEach((marker, markerId) => {
                if (markerId !== currentUser.id) {
                    map.removeLayer(marker);
                }
            });
            markers.clear();
            markers.set(currentUser.id, markers.get(currentUser.id));

            updateUI();
            showNotification('Viaje completado', 'success');
        }

        // Actualizar interfaz de usuario
        function updateUI() {
            const userTypeSelect = document.getElementById('userTypeSelect');
            const formTitle = document.getElementById('formTitle');
            const actionBtn = document.getElementById('actionBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const priceGroup = document.getElementById('priceGroup');
            const userId = document.getElementById('userId');
            const userStatus = document.getElementById('userStatus');
            const selectDestinationBtn = document.getElementById('selectDestinationBtn');
            const confirmButtons = document.getElementById('confirmButtons');

            // Actualizar información del usuario
            userId.textContent = currentUser.id;
            userStatus.textContent = getStatusText(currentUser.status);

            // Deshabilitar cambio de tipo durante viaje
            userTypeSelect.disabled = currentUser.status === 'in_trip';

            // Actualizar según tipo de usuario
            if (currentUser.type === 'client') {
                formTitle.textContent = 'Solicitar Taxi';
                actionBtn.textContent = '📍 Actualizar Mi Ubicación';
                priceGroup.style.display = 'none';

                if (currentUser.lat && !destinationCoords && currentUser.status === 'waiting') {
                    selectDestinationBtn.style.display = 'block';
                }
            } else {
                formTitle.textContent = 'Ofrecer Servicio';
                actionBtn.textContent = '🏍️ Estar Disponible';
                priceGroup.style.display = 'block';
                selectDestinationBtn.style.display = 'none';
                confirmButtons.style.display = 'none';
            }

            // Mostrar/ocultar botón cancelar
            if (currentRequest && (currentUser.status === 'requesting' || currentUser.status === 'available')) {
                cancelBtn.style.display = 'block';
                actionBtn.disabled = true;
                selectDestinationBtn.style.display = 'none';
            } else {
                cancelBtn.style.display = 'none';
                actionBtn.disabled = currentUser.status === 'in_trip';
            }

            // Deshabilitar controles durante viaje
            if (currentUser.status === 'in_trip') {
                document.getElementById('destination').disabled = true;
                document.getElementById('description').disabled = true;
                document.getElementById('price').disabled = true;
            } else {
                document.getElementById('destination').disabled = false;
                document.getElementById('description').disabled = false;
                document.getElementById('price').disabled = false;
            }
        }

        // Obtener texto de estado
        function getStatusText(status) {
            const statusTexts = {
                'waiting': '⏳ Esperando',
                'requesting': '🚕 Solicitando taxi',
                'available': '🏍️ Disponible',
                'matched': '✅ Emparejado',
                'in_trip': '🚗 En viaje'
            };
            return statusTexts[status] || status;
        }

        // Actualizar estado de conexión
        function updateConnectionStatus(connected) {
            const statusEl = document.getElementById('connectionStatus');
            if (connected) {
                statusEl.className = 'connection-status connected';
                statusEl.textContent = '🟢 Conectado a ChasquiX';
            } else {
                statusEl.className = 'connection-status disconnected';
                statusEl.textContent = '🔴 Desconectado';
            }
        }

        // Mostrar notificación
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = 'notification';

            // Colores según tipo
            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };

            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animación de entrada
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Remover después de 4 segundos
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Calcular distancia entre dos puntos (fórmula de Haversine)
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Actualizar ubicación en tiempo real (cada 30 segundos)
        function startLocationTracking() {
            setInterval(() => {
                if (navigator.geolocation && (currentUser.status === 'available' || currentUser.status === 'in_trip')) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const newLat = position.coords.latitude;
                            const newLng = position.coords.longitude;

                            // Solo actualizar si la posición cambió significativamente
                            const distance = calculateDistance(currentUser.lat, currentUser.lng, newLat, newLng);
                            if (distance > 0.01) { // ~10 metros
                                updateLocation(newLat, newLng);

                                // Publicar nueva ubicación si está activo
                                if (currentRequest) {
                                    const locationUpdate = {
                                        ...currentRequest,
                                        lat: newLat,
                                        lng: newLng,
                                        timestamp: Date.now()
                                    };

                                    const topic = currentUser.type === 'client' ?
                                        MQTT_CONFIG.topics.clients : MQTT_CONFIG.topics.drivers;

                                    publishMessage(topic, locationUpdate);
                                }
                            }
                        },
                        function(error) {
                            console.log('Error obteniendo ubicación:', error);
                        }
                    );
                }
            }, 30000); // 30 segundos
        }

        // Iniciar seguimiento de ubicación al cargar
        setTimeout(startLocationTracking, 5000);

        // Limpiar al cerrar la ventana
        window.addEventListener('beforeunload', function() {
            if (currentRequest) {
                cancelCurrentRequest();
            }
            if (mqttClient && mqttClient.isConnected()) {
                mqttClient.disconnect();
            }
        });

        // Función para simular datos de prueba (solo para desarrollo)
        function addTestData() {
            // Simular algunos clientes y conductores para pruebas
            const testClients = [{
                    userId: 'client_test_1',
                    action: 'request',
                    lat: -9.2955,
                    lng: -76.0083,
                    destinationLat: -9.2985,
                    destinationLng: -76.0053,
                    destination: 'Hospital de Tingo María',
                    description: 'Urgente, paciente embarazada',
                    timestamp: Date.now() - 120000
                },
                {
                    userId: 'client_test_2',
                    action: 'request',
                    lat: -9.2935,
                    lng: -76.0063,
                    destinationLat: -9.2915,
                    destinationLng: -76.0093,
                    destination: 'Universidad Nacional Agraria',
                    description: 'Estudiante con equipaje',
                    timestamp: Date.now() - 60000
                }
            ];

            const testDrivers = [{
                    userId: 'driver_test_1',
                    action: 'available',
                    lat: -9.2965,
                    lng: -76.0053,
                    description: 'Mototaxi Honda 150cc',
                    price: '12.00',
                    timestamp: Date.now() - 180000
                },
                {
                    userId: 'driver_test_2',
                    action: 'available',
                    lat: -9.2925,
                    lng: -76.0093,
                    description: 'Mototaxi Bajaj, casco incluido',
                    price: '15.00',
                    timestamp: Date.now() - 90000
                }
            ];

            // Agregar datos de prueba después de 3 segundos
            setTimeout(() => {
                testClients.forEach(client => {
                    handleClientMessage(client);
                });

                testDrivers.forEach(driver => {
                    handleDriverMessage(driver);
                });

                console.log('Datos de prueba agregados');
            }, 3000);
        }

        // Descomentar para agregar datos de prueba
        // addTestData();
    </script>
</body>

</html>
