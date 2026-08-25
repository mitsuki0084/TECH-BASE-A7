/**
 * main.js - フロントエンド共通および非同期処理
 *
 * 【このファイルに集約しているAPI関連処理】
 * 1. Open-Meteo ジオコーディングAPI・天気予報API（旅行日程分の日別天気カード表示）
 * 3. update_schedule.php 呼び出し（スケジュールのクリック編集・誰でも編集可）
 */

/**
 * 天気予報表示・スケジュールクリック編集
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeWeatherForecast();
    initializeScheduleEdit();
});

/* 天気予報 */

async function initializeWeatherForecast() {
    const container = document.getElementById('weather-forecast');
    const cards = document.getElementById('weather-cards');
    const fetchedAt = document.getElementById('weather-fetched-at');

    if (!container || !cards) return;

    const destination = container.dataset.destination;
    const startDate = container.dataset.startDate;
    const endDate = container.dataset.endDate;

    try {
        const location = await geocode(destination);

        if (!location) {
            throw new Error('目的地が見つかりません。');
        }

        const weather = await getWeather(
            location.latitude,
            location.longitude,
            startDate,
            endDate
        );

        if (!weather.daily || !weather.daily.time) {
            throw new Error('天気データがありません。');
        }

        const days = weather.daily.time.map((date, index) => `
            <div class="weather-card">
                <p class="weather-card-day">Day ${index + 1}</p>
                <p class="weather-card-date">
                    ${escapeHtml(formatDate(date))}
                </p>
                <p class="weather-card-icon">
                    ${weatherIcon(weather.daily.weather_code[index])}
                </p>
                <p class="weather-card-desc">
                    ${escapeHtml(weatherText(weather.daily.weather_code[index]))}
                </p>
                <p class="weather-card-temp">
                    最高 ${escapeHtml(String(
                        weather.daily.temperature_2m_max[index]
                    ))}℃ /
                    最低 ${escapeHtml(String(
                        weather.daily.temperature_2m_min[index]
                    ))}℃
                </p>
                <p class="weather-card-detail">
                    降水確率：
                    ${weather.daily.precipitation_probability_max?.[index] ?? '—'}%
                </p>
            </div>
        `).join('');

        cards.innerHTML = days;

        if (fetchedAt) {
            fetchedAt.textContent =
                `取得日時：${new Date().toLocaleString('ja-JP')}`;
        }
    } catch (error) {
        console.error('[Weather]', error);
        cards.innerHTML =
            '<p class="weather-error">天気情報の取得に失敗しました。</p>';
    }
}

async function geocode(destination) {
    const names = [
        destination,
        `${destination}市`,
        destination.replace(/市$/, '')
    ].filter((value, index, array) =>
        value && array.indexOf(value) === index
    );

    for (const name of names) {
        const url =
            'https://geocoding-api.open-meteo.com/v1/search?' +
            new URLSearchParams({
                name,
                count: '1',
                language: 'ja',
                format: 'json',
                countryCode: 'JP'
            });

        const response = await fetch(url);

        if (!response.ok) continue;

        const data = await response.json();

        if (data.results?.[0]) {
            return data.results[0];
        }
    }

    return null;
}

async function getWeather(latitude, longitude, startDate, endDate) {
    const url =
        'https://api.open-meteo.com/v1/forecast?' +
        new URLSearchParams({
            latitude,
            longitude,
            daily: [
                'weather_code',
                'temperature_2m_max',
                'temperature_2m_min',
                'precipitation_probability_max'
            ].join(','),
            start_date: startDate,
            end_date: endDate,
            timezone: 'auto'
        });

    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`天気APIエラー: ${response.status}`);
    }

    return response.json();
}

/* スケジュール編集 */

function initializeScheduleEdit() {
    const list = document.getElementById('sortable-schedule');

    if (!list) return;

    list.addEventListener('click', event => {
        const item = event.target.closest('.schedule-item');

        if (!item || !list.contains(item)) return;
        if (item.classList.contains('is-editing')) return;

        openScheduleEditor(item);
    });
}

function openScheduleEditor(item) {
    const view = item.querySelector('.schedule-item-view');

    if (!view) return;

    const form = document.createElement('form');
    form.className = 'schedule-edit-form';

    form.innerHTML = `
        <label>日程番号</label>
        <input name="day_number" type="number" min="1"
               value="${escapeAttribute(item.dataset.dayNumber)}" required>

        <label>スポット名</label>
        <input name="spot_name"
               value="${escapeAttribute(item.dataset.spotName)}" required>

        <label>時間帯</label>
        <input name="time_slot"
               value="${escapeAttribute(item.dataset.timeSlot)}">

        <label>メモ</label>
        <textarea name="memo" rows="3">${escapeHtml(
            item.dataset.memo
        )}</textarea>

        <div class="schedule-edit-actions">
            <button type="submit">保存</button>
            <button type="button" class="schedule-edit-cancel">
                キャンセル
            </button>
        </div>
    `;

    item.classList.add('is-editing');
    view.hidden = true;
    item.appendChild(form);

    form.querySelector('.schedule-edit-cancel')
        .addEventListener('click', () => {
            form.remove();
            view.hidden = false;
            item.classList.remove('is-editing');
        });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const formData = new FormData(form);

        const payload = {
            id: Number(item.dataset.id),
            day_number: Number(formData.get('day_number')),
            spot_name: String(formData.get('spot_name')).trim(),
            time_slot: String(formData.get('time_slot')).trim(),
            memo: String(formData.get('memo')).trim()
        };

        try {
            const response = await fetch(
                '/TECH-BASE-A7/update_schedule.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || '更新に失敗しました。');
            }

            item.dataset.dayNumber = payload.day_number;
            item.dataset.spotName = payload.spot_name;
            item.dataset.timeSlot = payload.time_slot;
            item.dataset.memo = payload.memo;

            const dayElement = view.querySelector('strong');
            if (dayElement) {
                dayElement.textContent = `Day ${payload.day_number}`;
            }

            const spans = view.querySelectorAll(':scope > span');

            if (spans[0]) {
                spans[0].textContent = payload.spot_name;
            }

            if (spans[1] && spans[1].classList.contains('schedule-edit-hint')) {
                spans[1].textContent = 'クリックして編集';
            }

            form.remove();
            view.hidden = false;
            item.classList.remove('is-editing');

            // 時間帯・メモの表示を最新状態にするため再読み込み
            window.location.reload();
        } catch (error) {
            console.error('[Schedule]', error);
            alert('スケジュールの更新に失敗しました。');
        }
    });
}

/* 共通関数 */

function formatDate(date) {
    const value = new Date(`${date}T00:00:00`);
    return `${value.getMonth() + 1}/${value.getDate()}`;
}

function weatherText(code) {
    if (code === 0) return '快晴';
    if ([1, 2, 3].includes(code)) return '晴れ・くもり';
    if ([45, 48].includes(code)) return '霧';
    if ([51, 53, 55, 56, 57].includes(code)) return '霧雨';
    if ([61, 63, 65, 66, 67].includes(code)) return '雨';
    if ([71, 73, 75, 77].includes(code)) return '雪';
    if ([80, 81, 82].includes(code)) return 'にわか雨';
    if ([85, 86].includes(code)) return 'にわか雪';
    if ([95, 96, 99].includes(code)) return '雷雨';
    return '不明';
}

function weatherIcon(code) {
    if (code === 0) return '☀️';
    if ([1, 2, 3].includes(code)) return '⛅';
    if ([61, 63, 65, 80, 81, 82].includes(code)) return '🌧️';
    if ([71, 73, 75, 77, 85, 86].includes(code)) return '❄️';
    if ([95, 96, 99].includes(code)) return '⛈️';
    return '🌤️';
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}

