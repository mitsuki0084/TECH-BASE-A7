/**
 * main.js - フロントエンド共通および非同期処理
 */

document.addEventListener('DOMContentLoaded', () => {
        console.log('main.js loaded');
    initializeSortable();
    initializeWeather();
});

/**
 * スケジュールのドラッグ＆ドロップ初期化
 */
function initializeSortable() {
    const scheduleList = document.getElementById('sortable-schedule');

    if (!scheduleList || typeof Sortable === 'undefined') {
        return;
    }

    Sortable.create(scheduleList, {
        animation: 150,
        draggable: '.sortable-item',

        onEnd: async () => {
            const schedules = [...scheduleList.querySelectorAll('.sortable-item')]
                .map((item, index) => ({
                    id: Number(item.dataset.id),
                    sort_order: index + 1
                }));

            try {
                const response = await fetch('update_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ schedules })
                });

                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || '保存に失敗しました。');
                }
            } catch (error) {
                console.error('並び順の保存に失敗しました:', error);
                alert('並び順の保存に失敗しました。ページを再読み込みしてください。');
            }
        }
    });
}

/**
 * 天気情報の初期化
 */
function initializeWeather() {
    const weatherContainer = document.getElementById('weather-info');

    if (!weatherContainer) {
        return;
    }

    const area = weatherContainer.dataset.area;

    if (!area) {
        renderWeatherError(weatherContainer);
        return;
    }

    fetchWeather(area, weatherContainer);
}

/**
 * Open-Meteoから天気情報を取得
 */
async function fetchWeather(area, container) {
    console.log('[API Task] Area Weather Fetching for:', area);

    try {
        const locationResponse = await fetch(
            `https://geocoding-api.open-meteo.com/v1/search?name=${
                encodeURIComponent(area)
            }&count=10&language=ja&format=json&countryCode=JP`
        );

        if (!locationResponse.ok) {
            throw new Error('地域情報の取得に失敗しました。');
        }

        const locationData = await locationResponse.json();

        // 日本の主要地域を直接補完
        const fallbackLocations = {
            '東京': { latitude: 35.6762, longitude: 139.6503 },
            '大阪': { latitude: 34.6937, longitude: 135.5023 },
            '京都': { latitude: 35.0116, longitude: 135.7681 },
            '札幌': { latitude: 43.0618, longitude: 141.3545 },
            '福岡': { latitude: 33.5904, longitude: 130.4017 },
            '名古屋': { latitude: 35.1815, longitude: 136.9066 }
        };

        const location =
            locationData.results?.[0] ||
            fallbackLocations[area];

        if (!location) {
            throw new Error(`地域が見つかりません: ${area}`);
        }

        const weatherResponse = await fetch(
            `https://api.open-meteo.com/v1/forecast?latitude=${
                location.latitude
            }&longitude=${
                location.longitude
            }&daily=weather_code,temperature_2m_max,temperature_2m_min&timezone=auto`
        );

        if (!weatherResponse.ok) {
            throw new Error('天気情報の取得に失敗しました。');
        }

        const weatherData = await weatherResponse.json();

        console.log('[API Task] Weather data received:', weatherData);

        renderWeather(container, area, weatherData);
    } catch (error) {
        console.error('[API Task] Weather fetch failed:', error);
        renderWeatherError(container);
    }
}

/**
 * 天気情報を表示
 */
function renderWeather(container, area, data) {
    const date = data.daily?.time?.[0];
    const weatherCode = data.daily?.weather_code?.[0];
    const maxTemperature = data.daily?.temperature_2m_max?.[0];
    const minTemperature = data.daily?.temperature_2m_min?.[0];

    if (
        !date ||
        weatherCode === undefined ||
        maxTemperature === undefined ||
        minTemperature === undefined
    ) {
        renderWeatherError(container);
        return;
    }

    container.innerHTML = `
        <h3>${escapeHtml(area)}の天気予報</h3>
        <p>${escapeHtml(date)}</p>
        <p>${getWeatherText(weatherCode)}</p>
        <p>
            最高気温：${escapeHtml(String(maxTemperature))}℃
            ／ 最低気温：${escapeHtml(String(minTemperature))}℃
        </p>
    `;
}

/**
 * 天気取得失敗時の表示
 */
function renderWeatherError(container) {
    container.innerHTML = `
        <h3>目的地の天気予報</h3>
        <p>天気情報を取得できませんでした。</p>
    `;
}

/**
 * WMO天気コードを日本語へ変換
 */
function getWeatherText(code) {
    if (code === 0) return '快晴';
    if ([1, 2, 3].includes(code)) return '晴れ・くもり';
    if ([45, 48].includes(code)) return '霧';
    if ([51, 53, 55, 56, 57].includes(code)) return '霧雨';
    if ([61, 63, 65, 66, 67].includes(code)) return '雨';
    if ([71, 73, 75, 77].includes(code)) return '雪';
    if ([80, 81, 82].includes(code)) return 'にわか雨';
    if ([85, 86].includes(code)) return 'にわか雪';
    if ([95, 96, 99].includes(code)) return '雷雨';

    return '天気不明';
}

/**
 * HTMLエスケープ
 */
function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
}