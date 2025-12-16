/**
 * Notification Sound System
 * Handles notification sounds for both admin and client interfaces
 */

class NotificationSound {
    constructor() {
        this.audioContext = null;
        this.isEnabled = this.getSoundPreference();
        this.volume = 0.5;
        this.soundType = 'default';
        this.initAudioContext();
        this.loadSettings();
    }

    // Initialize Web Audio API context
    initAudioContext() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            console.warn('Web Audio API not supported');
        }
    }

    // Get user's sound preference from localStorage
    getSoundPreference() {
        const preference = localStorage.getItem('notificationSoundEnabled');
        return preference !== 'false'; // Default to true if not set
    }

    // Save user's sound preference
    setSoundPreference(enabled) {
        localStorage.setItem('notificationSoundEnabled', enabled.toString());
        this.isEnabled = enabled;
    }

    // Create notification sound using Web Audio API
    playNotificationSound(type = null) {
        if (!this.isEnabled) return;

        // Use provided type or saved sound type
        const soundType = type || this.soundType;
        console.log('Attempting to play notification sound:', soundType); // Debug log

        // Try Web Audio API first
        if (this.audioContext) {
            try {
                // Resume audio context if suspended
                if (this.audioContext.state === 'suspended') {
                    this.audioContext.resume().then(() => {
                        this.playWebAudioSound(soundType);
                    });
                } else {
                    this.playWebAudioSound(soundType);
                }
            } catch (error) {
                console.warn('Web Audio API failed, trying fallback:', error);
                this.playBeep();
            }
        } else {
            console.log('Web Audio API not available, using fallback');
            this.playBeep();
        }
    }

    // Web Audio API sound generation
    playWebAudioSound(type) {
        try {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);

            // Different sound patterns for different notification types
            const soundPatterns = {
                'default': [
                    { frequency: 800, duration: 0.1 },
                    { frequency: 1000, duration: 0.1 }
                ],
                'success': [
                    { frequency: 600, duration: 0.1 },
                    { frequency: 800, duration: 0.1 },
                    { frequency: 1000, duration: 0.1 }
                ],
                'warning': [
                    { frequency: 400, duration: 0.2 },
                    { frequency: 300, duration: 0.2 }
                ],
                'error': [
                    { frequency: 200, duration: 0.3 },
                    { frequency: 150, duration: 0.3 }
                ],
                'request': [
                    { frequency: 500, duration: 0.15 },
                    { frequency: 700, duration: 0.15 },
                    { frequency: 900, duration: 0.15 }
                ],
                'gentle': [
                    { frequency: 400, duration: 0.2 },
                    { frequency: 500, duration: 0.2 }
                ],
                'alert': [
                    { frequency: 1000, duration: 0.1 },
                    { frequency: 800, duration: 0.1 },
                    { frequency: 1000, duration: 0.1 }
                ],
                'chime': [
                    { frequency: 523, duration: 0.2 }, // C5
                    { frequency: 659, duration: 0.2 }, // E5
                    { frequency: 784, duration: 0.3 }  // G5
                ]
            };

            const pattern = soundPatterns[type] || soundPatterns['default'];
            let currentTime = this.audioContext.currentTime;

            pattern.forEach((note, index) => {
                const osc = this.audioContext.createOscillator();
                const gain = this.audioContext.createGain();

                osc.connect(gain);
                gain.connect(this.audioContext.destination);

                osc.frequency.setValueAtTime(note.frequency, currentTime);
                osc.type = 'sine';

                gain.gain.setValueAtTime(0, currentTime);
                gain.gain.linearRampToValueAtTime(this.volume * 0.6, currentTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.01, currentTime + note.duration);

                osc.start(currentTime);
                osc.stop(currentTime + note.duration);

                currentTime += note.duration + 0.05; // Small gap between notes
            });

            console.log('Web Audio sound played successfully');

        } catch (error) {
            console.warn('Error playing Web Audio sound:', error);
            this.playBeep();
        }
    }

    // Play a simple beep sound (fallback)
    playBeep() {
        if (!this.isEnabled) return;

        console.log('Playing beep fallback sound'); // Debug log

        try {
            // Try multiple fallback methods
            this.playBeepMethod1();
        } catch (error) {
            console.warn('Beep method 1 failed, trying method 2:', error);
            try {
                this.playBeepMethod2();
            } catch (error2) {
                console.warn('All beep methods failed:', error2);
            }
        }
    }

    // Method 1: Data URL beep
    playBeepMethod1() {
        const audio = new Audio();
        const audioData = this.createBeepAudioData();
        audio.src = audioData;
        audio.volume = 0.5;
        audio.play().then(() => {
            console.log('Beep method 1 played successfully');
        }).catch(e => {
            console.warn('Beep method 1 failed:', e);
            throw e;
        });
    }

    // Method 2: Simple oscillator beep
    playBeepMethod2() {
        if (!this.audioContext) {
            throw new Error('No audio context available');
        }

        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);

        oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.3, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.2);

        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.2);

        console.log('Beep method 2 played successfully');
    }

    // Create beep audio data URL
    createBeepAudioData() {
        const sampleRate = 44100;
        const duration = 0.2;
        const frequency = 800;
        const samples = Math.floor(sampleRate * duration);
        const buffer = new ArrayBuffer(44 + samples * 2);
        const view = new DataView(buffer);

        // WAV header
        const writeString = (offset, string) => {
            for (let i = 0; i < string.length; i++) {
                view.setUint8(offset + i, string.charCodeAt(i));
            }
        };

        writeString(0, 'RIFF');
        view.setUint32(4, 36 + samples * 2, true);
        writeString(8, 'WAVE');
        writeString(12, 'fmt ');
        view.setUint32(16, 16, true);
        view.setUint16(20, 1, true);
        view.setUint16(22, 1, true);
        view.setUint32(24, sampleRate, true);
        view.setUint32(28, sampleRate * 2, true);
        view.setUint16(32, 2, true);
        view.setUint16(34, 16, true);
        writeString(36, 'data');
        view.setUint32(40, samples * 2, true);

        // Generate sine wave
        for (let i = 0; i < samples; i++) {
            const sample = Math.sin(2 * Math.PI * frequency * i / sampleRate) * 0.3;
            view.setInt16(44 + i * 2, sample * 32767, true);
        }

        const blob = new Blob([buffer], { type: 'audio/wav' });
        return URL.createObjectURL(blob);
    }

    // Toggle sound on/off
    toggleSound() {
        this.setSoundPreference(!this.isEnabled);
        return this.isEnabled;
    }

    // Check if sound is enabled
    isSoundEnabled() {
        return this.isEnabled;
    }

    // Load settings from localStorage
    loadSettings() {
        const settings = localStorage.getItem('notificationSoundSettings');
        if (settings) {
            try {
                const parsed = JSON.parse(settings);
                this.soundType = parsed.soundType || 'default';
                this.volume = parsed.volume || 0.5;
                this.isEnabled = parsed.enableSound !== false;
            } catch (e) {
                console.warn('Error loading sound settings:', e);
            }
        }
    }

    // Set sound type
    setSoundType(type) {
        this.soundType = type;
    }

    // Set volume (0.0 to 1.0)
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
    }

    // Get current volume
    getVolume() {
        return this.volume;
    }

    // Get current sound type
    getSoundType() {
        return this.soundType;
    }
}

// Global notification sound instance
window.notificationSound = new NotificationSound();

// Enable audio context on first user interaction
let audioContextEnabled = false;
function enableAudioContext() {
    if (!audioContextEnabled && window.notificationSound.audioContext) {
        window.notificationSound.audioContext.resume().then(() => {
            console.log('Audio context enabled');
            audioContextEnabled = true;
        });
    }
}

// Enable audio on any user interaction
document.addEventListener('click', enableAudioContext, { once: true });
document.addEventListener('keydown', enableAudioContext, { once: true });
document.addEventListener('touchstart', enableAudioContext, { once: true });

// Auto-play notification sound when page becomes visible (for new notifications)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.hasNewNotifications) {
        window.notificationSound.playNotificationSound('default');
        window.hasNewNotifications = false;
    }
});

// Add test sound function
window.testNotificationSound = function() {
    console.log('Testing notification sound...');
    window.notificationSound.playNotificationSound('default');
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSound;
}
