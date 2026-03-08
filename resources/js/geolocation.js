/**
 * Geolocation helper functions
 */

const defaultOptions = {
    enableHighAccuracy: true,
    timeout: 15000,
    maximumAge: 0
};

/**
 * Get current position as a Promise
 * @param {PositionOptions} options - Geolocation options
 * @returns {Promise<GeolocationPosition>}
 */
export function getCurrentPosition(options = {}) {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported by this browser'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            resolve,
            reject,
            { ...defaultOptions, ...options }
        );
    });
}

/**
 * Get formatted position data
 * @returns {Promise<{latitude: number, longitude: number, accuracy: number}>}
 */
export async function getFormattedPosition() {
    const position = await getCurrentPosition();

    return {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: Math.round(position.coords.accuracy)
    };
}

/**
 * Watch position changes
 * @param {function} callback - Callback function receiving position data
 * @param {function} errorCallback - Error callback
 * @param {PositionOptions} options - Geolocation options
 * @returns {number} Watch ID for clearing
 */
export function watchPosition(callback, errorCallback, options = {}) {
    if (!navigator.geolocation) {
        errorCallback(new Error('Geolocation is not supported'));
        return null;
    }

    return navigator.geolocation.watchPosition(
        (position) => {
            callback({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: Math.round(position.coords.accuracy)
            });
        },
        errorCallback,
        { ...defaultOptions, ...options }
    );
}

/**
 * Clear position watch
 * @param {number} watchId - Watch ID to clear
 */
export function clearWatch(watchId) {
    if (watchId && navigator.geolocation) {
        navigator.geolocation.clearWatch(watchId);
    }
}

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param {number} lat1 - First latitude
 * @param {number} lon1 - First longitude
 * @param {number} lat2 - Second latitude
 * @param {number} lon2 - Second longitude
 * @returns {number} Distance in meters
 */
export function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth's radius in meters
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

function toRad(deg) {
    return deg * (Math.PI / 180);
}

/**
 * Format distance for display
 * @param {number} meters - Distance in meters
 * @returns {string} Formatted distance
 */
export function formatDistance(meters) {
    if (meters < 1000) {
        return `${Math.round(meters)} m`;
    }
    return `${(meters / 1000).toFixed(2)} km`;
}

/**
 * Get location error message in Spanish
 * @param {GeolocationPositionError} error - Error object
 * @returns {string} Error message
 */
export function getErrorMessage(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            return 'Permiso de ubicación denegado';
        case error.POSITION_UNAVAILABLE:
            return 'Ubicación no disponible';
        case error.TIMEOUT:
            return 'Tiempo de espera agotado';
        default:
            return 'Error desconocido al obtener ubicación';
    }
}

// Export for Alpine.js
if (typeof window !== 'undefined') {
    window.geolocation = {
        getCurrentPosition,
        getFormattedPosition,
        watchPosition,
        clearWatch,
        calculateDistance,
        formatDistance,
        getErrorMessage
    };
}
