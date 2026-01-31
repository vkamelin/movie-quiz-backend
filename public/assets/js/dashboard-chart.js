// Render dashboard status chart
const canvas = document.getElementById('statusChart');
if (canvas) {
    const ctx = canvas.getContext('2d');
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const success = JSON.parse(canvas.dataset.success || '[]');
    const failed = JSON.parse(canvas.dataset.failed || '[]');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'успешные',
                    data: success,
                    borderColor: 'rgb(25, 135, 84)',
                    tension: 0.1,
                },
                {
                    label: 'неудачные',
                    data: failed,
                    borderColor: 'rgb(220, 53, 69)',
                    tension: 0.1,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true },
            },
        },
    });
}
