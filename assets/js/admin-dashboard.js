/**
 * Ref247 Admin Dashboard Charts
 */
/* global Chart, ref247Data */
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined' || typeof ref247Data === 'undefined') {
        return;
    }

    // Commission Chart
    const ctxCommissions = document.getElementById('ref247CommissionChart');
    if (ctxCommissions) {
        new Chart(ctxCommissions.getContext('2d'), {
            type: 'line',
            data: {
                labels: ref247Data.pendingLabels,
                datasets: [
                    {
                        label: 'Pending Commissions',
                        data: ref247Data.pendingSeries,
                        borderColor: 'rgba(255,193,7,1)',
                        backgroundColor: 'rgba(255,193,7,0.2)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Paid Commissions',
                        data: ref247Data.paidSeries,
                        borderColor: 'rgba(76,175,80,1)',
                        backgroundColor: 'rgba(76,175,80,0.2)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return '$' + value.toFixed(2); } }
                    }
                }
            }
        });
    }

    // Referrals Chart
    const ctxReferrals = document.getElementById('ref247ReferralsChart');
    if (ctxReferrals) {
        new Chart(ctxReferrals.getContext('2d'), {
            type: 'line',
            data: {
                labels: ref247Data.referralsLabels,
                datasets: [
                    {
                        label: 'Referrals',
                        data: ref247Data.referralsSeries,
                        borderColor: 'rgba(63,81,181,1)',
                        backgroundColor: 'rgba(63,81,181,0.2)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(value) { return value.toFixed(0); } }
                    }
                }
            }
        });
    }
});
