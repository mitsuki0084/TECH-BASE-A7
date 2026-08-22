<?php
// filepath: c:\xampp\htdocs\TECH-BASE-A7\weather_test.php

require_once 'includes/header.php';
?>

<h2>天気表示テスト</h2>

<div class="weather-test-card card" data-area="名古屋">
    <p>天気情報を読み込み中...</p>
</div>

<div class="weather-test-card card" data-area="東京">
    <p>天気情報を読み込み中...</p>
</div>

<div class="weather-test-card card" data-area="大阪">
    <p>天気情報を読み込み中...</p>
</div>

<script>
(() => {
    document.addEventListener('DOMContentLoaded', () => {
        document
            .querySelectorAll('.weather-test-card')
            .forEach(loadTestWeather);
    });

    async function loadTestWeather(container) {
        const area = container.dataset.area;

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
                throw new Error('地域検索APIに接続できません。');
            }

            const locationData = await locationResponse.json();
            const location = locationData.results?.[0];

            if (!location) {
                throw new Error(`地域が見つかりません: ${area}`);
            }

            const weatherResponse = await fetch(
                'https://api.open-meteo.com/v1/forecast?' +
                new URLSearchParams({
                    latitude: location.latitude,
                    longitude: location.longitude,
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
                throw new Error('天気APIに接続できません。');
            }

            const weather = await weatherResponse.json();
            const daily = weather.daily;

            if (!daily?.time) {
                throw new Error('天気データがありません。');
            }

            let html = `
                <h3>${escapeTestHtml(area)}の7日間予報</h3>
                <p>データ取得日時：
                    ${escapeTestHtml(new Date().toLocaleString('ja-JP'))}
                </p>
                <ul>
            `;

            daily.time.forEach((date, index) => {
                html += `
                    <li>
                        ${escapeTestHtml(date)}
                        ：${getTestWeatherText(daily.weather_code[index])}
                        ／最高 ${escapeTestHtml(
                            String(daily.temperature_2m_max[index])
                        )}℃
                        ／最低 ${escapeTestHtml(
                            String(daily.temperature_2m_min[index])
                        )}℃
                    </li>
                `;
            });

            container.innerHTML = `${html}</ul>`;
        } catch (error) {
            console.error('[Weather Test]', error);
            container.innerHTML = `
                <h3>${escapeTestHtml(area)}の天気予報</h3>
                <p>天気情報を取得できませんでした。</p>
                <small>${escapeTestHtml(error.message)}</small>
            `;
        }
    }

    function getTestWeatherText(code) {
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

    function escapeTestHtml(value) {
        const element = document.createElement('div');
        element.textContent = value;
        return element.innerHTML;
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>