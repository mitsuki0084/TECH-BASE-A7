/**
 * main.js - フロントエンド共通および非同期処理
 */

document.addEventListener('DOMContentLoaded', () => {
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

    if (scheduleList.dataset.sortableInitialized === 'true') {
        return;
    }

    scheduleList.dataset.sortableInitialized = 'true';

    Sortable.create(scheduleList, {
        animation: 150,
        draggable: '.sortable-item',

        onEnd: async () => {
            const schedules = [
                ...scheduleList.querySelectorAll('.sortable-item')
            ].map((item, index) => ({
                id: Number(item.dataset.id),
                sort_order: index + 1
            }));

            if (schedules.length === 0) {
                return;
            }

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

    const area = weatherContainer.dataset.area?.trim();

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
    try {
        const locationResponse = await fetch(
            'https://geocoding-api.open-meteo.com/v1/search?' +
            new URLSearchParams({
                name: area,
                count: '1',
                language: 'ja',
                format: 'json',
                countryCode: 'JP'
            })
        );

        if (!locationResponse.ok) {
            throw new Error('地域情報の取得に失敗しました。');
        }

        const locationData = await locationResponse.json();
        const location = locationData.results?.[0];

        if (!location) {
            throw new Error(`地域が見つかりません: ${area}`);
        }

        const weatherResponse = await fetch(
            'https://api.open-meteo.com/v1/forecast?' +
            new URLSearchParams({
                latitude: String(location.latitude),
                longitude: String(location.longitude),
                daily: [
                    'weather_code',
                    'temperature_2m_max',
                    'temperature_2m_min'
                ].join(','),
                timezone: 'auto',
                forecast_days: '7'
            })
        );

        if (!weatherResponse.ok) {
            throw new Error('天気情報の取得に失敗しました。');
        }

        const weatherData = await weatherResponse.json();

        // APIレスポンスを受信した時刻
        const fetchedAt = new Date();

        renderWeather(container, location.name || area, weatherData, fetchedAt);
    } catch (error) {
        console.error('[Weather]', error);
        renderWeatherError(container);
    }
}

/**
 * 7日間の天気情報を表示
 */
function renderWeather(container, area, data, fetchedAt) {
    const daily = data.daily;

    if (
        !daily?.time ||
        !daily.weather_code ||
        !daily.temperature_2m_max ||
        !daily.temperature_2m_min
    ) {
        renderWeatherError(container);
        return;
    }

    let html = `
        <h3>${escapeHtml(area)}の7日間天気予報</h3>
        <p>
            天気情報取得日時：
            ${escapeHtml(fetchedAt.toLocaleString('ja-JP'))}
        </p>
        <ul class="weather-forecast">
    `;

    daily.time.forEach((date, index) => {
        const weatherCode = daily.weather_code[index];
        const maxTemperature = daily.temperature_2m_max[index];
        const minTemperature = daily.temperature_2m_min[index];

        html += `
            <li>
                <strong>${escapeHtml(date)}</strong>
                <span>天気：${escapeHtml(getWeatherText(weatherCode))}</span>
                <span>
                    最高：${escapeHtml(String(maxTemperature))}℃
                    ／最低：${escapeHtml(String(minTemperature))}℃
                </span>
            </li>
        `;
    });

    html += '</ul>';
    container.innerHTML = html;
}

/**
 * 天気情報取得失敗時の表示
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
    element.textContent = value ?? '';
    return element.innerHTML;
}