/**
 * main.js - フロントエンド共通および非同期処理
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. ドラッグ＆ドロップ（Sortable.js）初期化処理
    const el = document.getElementById('sortable-schedule');
    if (el) {
        Sortable.create(el, {
            animation: 150,
            onEnd: function (evt) {
                // 並び替え終了時に実行される処理
                const orderData = [];
                const items = el.querySelectorAll('.sortable-item');
                items.forEach((item, index) => {
                    orderData.push({
                        id: item.dataset.id,
                        sort_order: index + 1
                    });
                });

                // 並び替え結果をサーバーへ非同期送信 (update_order.php)
                fetch('update_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ schedules: orderData })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('並び替え順序の保存に失敗しました。');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }

    // 2. 天気APIデータ取得処理関数
    const weatherContainer = document.getElementById('weather-info');
    if (weatherContainer) {
        const area = weatherContainer.dataset.area; // 例: "Tokyo"
        fetchWeather(area);
    }
});

/**
 * 外部天気APIから天気データを取得して画面に表示する
 * @param {string} area - 取得対象のエリア名
 */
function fetchWeather(area) {
    // OpenWeatherMap等の外部APIからデータを取得する処理をバックエンド担当者が実装
    // ここでは受け取ったデータを描画するパーツロジックの呼び出し枠を定義
    console.log(`[API Task] Area Weather Fetching for: ${area}`);
}