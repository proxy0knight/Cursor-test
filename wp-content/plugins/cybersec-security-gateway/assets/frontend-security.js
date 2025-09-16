/**
 * CyberSec Frontend Security
 * Device fingerprinting and handshake management
 */

class CyberSecSecurity {
    constructor() {
        this.instanceId = null;
        this.sessionToken = null;
        this.handshakeKey = null;
        this.deviceId = null;
        this.deviceFingerprint = null;
        this.isAuthenticated = false;
        
        this.init();
    }
    
    init() {
        // Load saved instance data
        this.loadInstanceData();
        
        // Generate device fingerprint
        this.generateDeviceFingerprint();
        
        // Set up request interceptor
        this.setupRequestInterceptor();
        
        // Set up periodic handshake
        this.setupPeriodicHandshake();
        
        // Handle page visibility changes
        this.setupVisibilityHandler();
    }
    
    loadInstanceData() {
        const savedData = localStorage.getItem('cybersec_instance');
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                this.instanceId = data.instanceId;
                this.sessionToken = data.sessionToken;
                this.handshakeKey = data.handshakeKey;
                this.deviceId = data.deviceId;
                this.isAuthenticated = data.isAuthenticated || false;
                
                // Verify instance is still valid
                this.verifyInstance();
            } catch (e) {
                console.error('Failed to load instance data:', e);
                this.clearInstanceData();
            }
        }
    }
    
    saveInstanceData() {
        const data = {
            instanceId: this.instanceId,
            sessionToken: this.sessionToken,
            handshakeKey: this.handshakeKey,
            deviceId: this.deviceId,
            isAuthenticated: this.isAuthenticated,
            timestamp: Date.now()
        };
        
        localStorage.setItem('cybersec_instance', JSON.stringify(data));
    }
    
    clearInstanceData() {
        localStorage.removeItem('cybersec_instance');
        this.instanceId = null;
        this.sessionToken = null;
        this.handshakeKey = null;
        this.deviceId = null;
        this.isAuthenticated = false;
    }
    
    async generateDeviceFingerprint() {
        const fingerprint = {
            user_agent: navigator.userAgent,
            screen_resolution: `${screen.width}x${screen.height}`,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            language: navigator.language,
            platform: navigator.platform,
            hardware_concurrency: navigator.hardwareConcurrency || 0,
            device_memory: navigator.deviceMemory || 0,
            canvas_fingerprint: await this.getCanvasFingerprint(),
            webgl_fingerprint: await this.getWebGLFingerprint(),
            audio_fingerprint: await this.getAudioFingerprint(),
            fonts: await this.getFonts(),
            plugins: this.getPlugins(),
            touch_support: this.getTouchSupport(),
            battery_info: await this.getBatteryInfo(),
            connection_info: this.getConnectionInfo(),
            media_devices: await this.getMediaDevices(),
            permissions: await this.getPermissions()
        };
        
        this.deviceFingerprint = await this.hashFingerprint(fingerprint);
        return fingerprint;
    }
    
    async getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = 200;
            canvas.height = 50;
            
            // Draw text
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('CyberSec Platform', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('Device Fingerprint', 4, 35);
            
            // Draw geometric shapes
            ctx.globalCompositeOperation = 'multiply';
            ctx.fillStyle = 'rgb(255,0,255)';
            ctx.beginPath();
            ctx.arc(50, 50, 50, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            ctx.fillStyle = 'rgb(0,255,255)';
            ctx.beginPath();
            ctx.arc(100, 50, 50, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            ctx.fillStyle = 'rgb(255,255,0)';
            ctx.beginPath();
            ctx.arc(75, 100, 50, 0, Math.PI * 2, true);
            ctx.closePath();
            ctx.fill();
            
            return canvas.toDataURL();
        } catch (e) {
            return 'canvas_error';
        }
    }
    
    async getWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (!gl) {
                return 'webgl_not_supported';
            }
            
            const fingerprint = {
                vendor: gl.getParameter(gl.VENDOR),
                renderer: gl.getParameter(gl.RENDERER),
                version: gl.getParameter(gl.VERSION),
                shading_language_version: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                extensions: gl.getSupportedExtensions(),
                parameters: {
                    max_texture_size: gl.getParameter(gl.MAX_TEXTURE_SIZE),
                    max_viewport_dims: gl.getParameter(gl.MAX_VIEWPORT_DIMS),
                    max_vertex_attribs: gl.getParameter(gl.MAX_VERTEX_ATTRIBS),
                    max_varying_vectors: gl.getParameter(gl.MAX_VARYING_VECTORS),
                    aliased_line_width_range: gl.getParameter(gl.ALIASED_LINE_WIDTH_RANGE),
                    aliased_point_size_range: gl.getParameter(gl.ALIASED_POINT_SIZE_RANGE)
                }
            };
            
            return JSON.stringify(fingerprint);
        } catch (e) {
            return 'webgl_error';
        }
    }
    
    async getAudioFingerprint() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const analyser = audioContext.createAnalyser();
            const gainNode = audioContext.createGain();
            const scriptProcessor = audioContext.createScriptProcessor(4096, 1, 1);
            
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(10000, audioContext.currentTime);
            
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            
            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            const audioData = new Float32Array(analyser.frequencyBinCount);
            
            return new Promise((resolve) => {
                scriptProcessor.onaudioprocess = () => {
                    analyser.getFloatFrequencyData(audioData);
                    const fingerprint = Array.from(audioData).slice(0, 30).join(',');
                    audioContext.close();
                    resolve(fingerprint);
                };
                
                oscillator.start(0);
            });
        } catch (e) {
            return 'audio_error';
        }
    }
    
    async getFonts() {
        const fonts = [
            'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana',
            'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS',
            'Trebuchet MS', 'Arial Black', 'Impact', 'Tahoma', 'Century Gothic'
        ];
        
        const availableFonts = [];
        
        for (const font of fonts) {
            if (this.isFontAvailable(font)) {
                availableFonts.push(font);
            }
        }
        
        return availableFonts;
    }
    
    isFontAvailable(font) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        ctx.font = '72px monospace';
        const baselineWidth = ctx.measureText('mmmmmmmmmmlli').width;
        
        ctx.font = `72px ${font}, monospace`;
        const width = ctx.measureText('mmmmmmmmmmlli').width;
        
        return width !== baselineWidth;
    }
    
    getPlugins() {
        const plugins = [];
        for (let i = 0; i < navigator.plugins.length; i++) {
            plugins.push(navigator.plugins[i].name);
        }
        return plugins;
    }
    
    getTouchSupport() {
        return {
            touch_events: 'ontouchstart' in window,
            touch_points: navigator.maxTouchPoints || 0,
            pointer_events: 'onpointerdown' in window,
            ms_pointer_events: 'onmspointerdown' in window
        };
    }
    
    async getBatteryInfo() {
        try {
            if ('getBattery' in navigator) {
                const battery = await navigator.getBattery();
                return {
                    charging: battery.charging,
                    charging_time: battery.chargingTime,
                    discharging_time: battery.dischargingTime,
                    level: battery.level
                };
            }
        } catch (e) {
            // Battery API not available
        }
        return null;
    }
    
    getConnectionInfo() {
        if ('connection' in navigator) {
            const conn = navigator.connection;
            return {
                effective_type: conn.effectiveType,
                downlink: conn.downlink,
                rtt: conn.rtt,
                save_data: conn.saveData
            };
        }
        return null;
    }
    
    async getMediaDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.map(device => ({
                kind: device.kind,
                label: device.label,
                device_id: device.deviceId ? 'present' : 'absent'
            }));
        } catch (e) {
            return [];
        }
    }
    
    async getPermissions() {
        const permissions = {};
        const permissionNames = ['camera', 'microphone', 'geolocation', 'notifications'];
        
        for (const permission of permissionNames) {
            try {
                if ('permissions' in navigator) {
                    const result = await navigator.permissions.query({ name: permission });
                    permissions[permission] = result.state;
                }
            } catch (e) {
                permissions[permission] = 'unknown';
            }
        }
        
        return permissions;
    }
    
    async hashFingerprint(fingerprint) {
        const fingerprintString = JSON.stringify(fingerprint);
        const encoder = new TextEncoder();
        const data = encoder.encode(fingerprintString);
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
    
    setupRequestInterceptor() {
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            if (this.isAuthenticated && this.isInternalRequest(url)) {
                options.headers = options.headers || {};
                options.headers['X-Instance-ID'] = this.instanceId;
                options.headers['X-Session-Token'] = this.sessionToken;
                
                // Add handshake data
                const handshakeData = {
                    session_token: this.sessionToken,
                    handshake_key: this.handshakeKey,
                    device_fingerprint: this.deviceFingerprint,
                    timestamp: Date.now()
                };
                
                if (options.method === 'GET') {
                    url += (url.includes('?') ? '&' : '?') + 'handshake_data=' + encodeURIComponent(JSON.stringify(handshakeData));
                } else {
                    options.body = options.body ? JSON.stringify(JSON.parse(options.body)) : JSON.stringify(handshakeData);
                }
            }
            
            return originalFetch(url, options);
        };
        
        // Intercept XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._method = method;
            this._url = url;
            return originalXHROpen.apply(this, [method, url, ...args]);
        };
        
        XMLHttpRequest.prototype.send = function(data) {
            if (window.cybersecSecurity && window.cybersecSecurity.isAuthenticated && window.cybersecSecurity.isInternalRequest(this._url)) {
                this.setRequestHeader('X-Instance-ID', window.cybersecSecurity.instanceId);
                this.setRequestHeader('X-Session-Token', window.cybersecSecurity.sessionToken);
                
                const handshakeData = {
                    session_token: window.cybersecSecurity.sessionToken,
                    handshake_key: window.cybersecSecurity.handshakeKey,
                    device_fingerprint: window.cybersecSecurity.deviceFingerprint,
                    timestamp: Date.now()
                };
                
                if (this._method === 'GET') {
                    this._url += (this._url.includes('?') ? '&' : '?') + 'handshake_data=' + encodeURIComponent(JSON.stringify(handshakeData));
                } else {
                    data = data ? JSON.stringify(JSON.parse(data)) : JSON.stringify(handshakeData);
                }
            }
            
            return originalXHRSend.apply(this, [data]);
        };
    }
    
    isInternalRequest(url) {
        return url.startsWith('/') || url.includes(window.location.hostname);
    }
    
    setupPeriodicHandshake() {
        // Perform handshake every 5 minutes
        setInterval(() => {
            if (this.isAuthenticated) {
                this.performHandshake();
            }
        }, 5 * 60 * 1000);
    }
    
    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isAuthenticated) {
                // Perform handshake when page becomes visible
                this.performHandshake();
            }
        });
    }
    
    async performHandshake() {
        if (!this.instanceId || !this.handshakeKey) {
            return;
        }
        
        try {
            const response = await fetch('/api/handshake', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Instance-ID': this.instanceId
                },
                body: JSON.stringify({
                    handshake_data: {
                        session_token: this.sessionToken,
                        handshake_key: this.handshakeKey,
                        device_fingerprint: this.deviceFingerprint,
                        timestamp: Date.now()
                    }
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                console.error('Handshake failed:', result.error);
                this.handleHandshakeFailure(result.error);
            }
        } catch (error) {
            console.error('Handshake error:', error);
            this.handleHandshakeFailure('Network error');
        }
    }
    
    async verifyInstance() {
        if (!this.instanceId) {
            return;
        }
        
        try {
            const response = await fetch('/api/handshake', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Instance-ID': this.instanceId
                },
                body: JSON.stringify({
                    handshake_data: {
                        session_token: this.sessionToken,
                        handshake_key: this.handshakeKey,
                        device_fingerprint: this.deviceFingerprint,
                        timestamp: Date.now()
                    }
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.isAuthenticated = true;
                this.saveInstanceData();
            } else {
                this.clearInstanceData();
                this.redirectToLogin();
            }
        } catch (error) {
            console.error('Instance verification failed:', error);
            this.clearInstanceData();
            this.redirectToLogin();
        }
    }
    
    handleHandshakeFailure(error) {
        if (error === 'Instance expired' || error === 'Invalid instance') {
            this.clearInstanceData();
            this.redirectToLogin();
        }
    }
    
    redirectToLogin() {
        window.location.href = '/login';
    }
    
    async login(username, password) {
        const deviceInfo = await this.generateDeviceFingerprint();
        
        try {
            const response = await fetch('/api/auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'login',
                    username: username,
                    password: password,
                    device_fingerprint: this.deviceFingerprint,
                    device_info: deviceInfo
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                this.instanceId = data.instance.instance_id;
                this.sessionToken = data.instance.session_token;
                this.handshakeKey = data.instance.handshake_key;
                this.deviceId = data.instance.device_id;
                this.isAuthenticated = true;
                
                this.saveInstanceData();
                return { success: true, user: data.user };
            } else {
                return { success: false, error: result.error };
            }
        } catch (error) {
            return { success: false, error: 'Network error' };
        }
    }
    
    async register(username, email, password) {
        const deviceInfo = await this.generateDeviceFingerprint();
        
        try {
            const response = await fetch('/api/auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'register',
                    username: username,
                    email: email,
                    password: password,
                    device_fingerprint: this.deviceFingerprint,
                    device_info: deviceInfo
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const data = result.data;
                this.instanceId = data.instance.instance_id;
                this.sessionToken = data.instance.session_token;
                this.handshakeKey = data.instance.handshake_key;
                this.deviceId = data.instance.device_id;
                this.isAuthenticated = true;
                
                this.saveInstanceData();
                return { success: true, user: data.user };
            } else {
                return { success: false, error: result.error };
            }
        } catch (error) {
            return { success: false, error: 'Network error' };
        }
    }
    
    logout() {
        this.clearInstanceData();
        this.redirectToLogin();
    }
}

// Initialize security system
window.cybersecSecurity = new CyberSecSecurity();