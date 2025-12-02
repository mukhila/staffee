const statusChartConfig = {
	series: [70],
	chart: {
		type: 'radialBar',
		offsetY: 0,
		height: 350,
		sparkline: { enabled: true }
	},
	plotOptions: {
		radialBar: {
			startAngle: -90,
			endAngle: 90,
			track: {
				background: "var(--bs-light)",
				strokeWidth: '100%',
				margin: 25
			},
			dataLabels: {
				name: { show: false },
				value: {
					show: true,
					offsetY: -50,
					fontSize: '30px',
					fontFamily: 'var(--bs-body-font-family)',
					fontWeight: 800,
					color: 'var(--bs-dark)',
				},
			}
		}
	},
	grid: {
		padding: {
			top: 0,
			bottom: 0,
			left: 0,
			right: 0
		}
	},
	fill: {
		colors: ['var(--bs-primary)']
	}	
}
const statusChart = document.querySelector("#statusChart");
if (typeof statusChart !== undefined && statusChart !== null) {
    const chartInit = new ApexCharts(statusChart, statusChartConfig);
	chartInit.render();
}



const totalRevenueChartConfig = {
	series: [
		{
			data: [4, 6, 3, 7, 5, 10, 4, 8, 3, 6, 5]
		}
	],
	chart: {
		type: 'bar',
		height: 65,
		sparkline:{
			enabled: true
		}
	},
	plotOptions: {
		bar: {
			columnWidth: '40%',
			borderRadius: 2,
			distributed: true
		}
	},
	colors: ['#e6e6e6'],
	states: {
		normal: {
			filter: { type: 'none' }
		},
		hover: {
			filter: {
				type: 'none'
			},
			color: '#000'
		},
		active: {
			filter: {
				type: 'none'
			}
		}
	},
	tooltip: {
		enabled: false,
		y: {
			formatter: function (val) {
				return val;
			}
		}
	}
}
const totalRevenueChart = document.querySelector("#totalRevenueChart");
if (typeof totalRevenueChart !== undefined && totalRevenueChart !== null) {
    const chartInit = new ApexCharts(totalRevenueChart, totalRevenueChartConfig);
	chartInit.render();
}



const summeryChartConfig = {
	series: [
		{
			name: "Order",
            data: [300000, 80000, 300000, 300000, 290000, 210000, 350000, 500000, 380000]
		},
		{
			name: 'Income Growth',
            data: [0, 200000, 350000, 180000, 190000, 400000, 400000, 280000, 220000]
		}
	],
	chart: {
		height: 325,
		type: 'line',
		zoom: {
            enabled: false
		},
		toolbar:{
			show: false
		},
	},
	colors:[
		"var(--bs-secondary)",
		"var(--bs-primary)"
	],
	dataLabels: {
		enabled: false
	},
	stroke: {
		width: [2, 2],
		curve: 'smooth',
		dashArray: [8, 0]
	},
	markers: {
		size: 0,
		hover: {
			sizeOffset: 6
		}
	},
	yaxis: {
        min: 500000,
        max: 0,
        tickAmount: 5,
		labels: {
			formatter: function (value) {
				return (value / 1000) + "K";
			},
			style: {
				colors: 'var(--bs-body-color)',
				fontSize: '13px',
				fontFamily: 'var(--bs-body-font-family)'
			}
		}
    },
	xaxis: {
		categories: ['Jan', 'Feb', 'Mar', 'May', 'Jun', 'July', 'Aug', 'Sep'],
		axisBorder: {
			color: 'var(--bs-border-color)',
		},
		axisTicks: {
			show: false,
		},
		labels: {
			style: {
				colors: 'var(--bs-body-color)',
				fontSize: '13px',
				fontFamily: 'var(--bs-body-font-family)'
			}
		}
	},
	tooltip: {
		y: [
            {
				title: {
					formatter: function (val) {
						return val + " per session"
					}
				}
            },
            {
				title: {
					formatter: function (val) {
						return val;
					}
				}
            }
		]
	},
	grid: {
		borderColor: 'var(--bs-border-color)',
	},
	legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        markers: {
			size: 5,
            shape: 'circle',
            radius: 10,
			width: 10,
			height: 10,
		},
		labels: {
			colors: 'var(--bs-heading-color)',
			fontFamily: 'var(--bs-body-font-family)',
			fontSize: '13px',
        }
	}
}
const summeryChart = document.querySelector("#summeryChart");
if (typeof summeryChart !== undefined && summeryChart !== null) {
    const chartInit = new ApexCharts(summeryChart, summeryChartConfig);
	chartInit.render();
}



const newCustomersConfig = {
	series: [
		{
			name: "New Customers",
			data: [50, 80, 120, 150, 200, 180, 250, 300]
		}
	],
	chart: {
		height: 300,
		type: 'area',
		toolbar: { show: false },
	},
	colors: [
		"var(--bs-primary)",
	],
	dataLabels: { enabled: false },
	stroke: {
		curve: 'smooth',
		width: 2,
	},
	legend: { show: false },
	grid: {
		show: true,
		strokeDashArray: 3,
		borderColor: 'var(--bs-border-color)',
	},
	yaxis: {
		min: 0,
		max: 400,
		tickAmount: 4,
		labels: {
			style: {
				colors: 'var(--bs-body-color)',
				fontSize: '14px',
			},
			formatter: function (value) {
				return value;
			}
		},
	},
	xaxis: {
		categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
		labels: {
			style: {
				colors: 'var(--bs-body-color)',
				fontSize: '12px',
			},
		},
		axisTicks: { show: false },
		axisBorder: { show: false },
	},
	fill: {
		type: 'gradient',
		gradient: {
			colorStops: [
				[
					{ offset: 0, color: 'var(--bs-primary)', opacity: 0.2 },
					{ offset: 50, color: 'var(--bs-primary)', opacity: 0.1 },
					{ offset: 80, color: 'var(--bs-primary)', opacity: 0 }
				]
			]
		},
	},
	tooltip: {
		x: { show: true },
		y: {
			formatter: function (val) {
				return val.toLocaleString();
			}
		},
		theme: "light"
	},
	responsive: [{
		breakpoint: 575,
		options: {
			chart: { height: 200 },
			yaxis: {
				labels: { style: { fontSize: '11px' } },
			},
			xaxis: {
				labels: { style: { fontSize: '11px' } },
			},
		},
	}]
}
const newCustomersChart = document.querySelector("#newCustomersChart");
if (typeof newCustomersChart !== undefined && newCustomersChart !== null) {
	const chartInit = new ApexCharts(newCustomersChart, newCustomersConfig);
	chartInit.render();
}



const ratingChartConfig = {
	chart: {
        type: 'line',
        height: 80,
        width: 150,
        toolbar: { show: false },
        zoom: { enabled: false }
	},
	series: [{
        name: 'Rate',
        data: [20, 35, 30, 50, 44, 40, 45]
	}],
	stroke: {
        curve: 'smooth',
        width: 2
	},
	markers: {
        size: 3,
        colors: ['#fff'],
        strokeColors: 'var(--bs-success)',
        strokeWidth: 2,
        hover: {
			size: 8
        }
	},
	tooltip: {
        enabled: true,
        y: {
			formatter: function (val) {
				return "Rate " + val;
			}
        }
	},
	grid: {
		show: false
	},
	xaxis: {
		labels: { show: false },
		axisTicks: { show: false },
		axisBorder: { show: false }
	},
	yaxis: { 
		labels: { show: false },
		axisTicks: { show: false },
		axisBorder: { show: false }
	},
	colors: ['var(--bs-success)']
}
const ratingChart = document.querySelector("#ratingChart");
if (typeof ratingChart !== undefined && ratingChart !== null) {
	const chartInit = new ApexCharts(ratingChart, ratingChartConfig);
	chartInit.render();
}


	
if ($('#dt_RecentOrders').length) {
	const dt_RecentOrders = $('#dt_RecentOrders').DataTable({
		searching: true,
		pageLength: 6,
		select: false,
		lengthChange: false,
		info: true,
		paging: true,
		language: {
			search: "",
			searchPlaceholder: 'Search',
			paginate: {
				previous: "<i class='fi fi-rr-angle-left'></i>",
				next: "<i class='fi fi-rr-angle-right'></i>",
				first: "<i class='fi fi-rr-angle-double-left'></i>",
				last: "<i class='fi fi-rr-angle-double-right'></i>"
			},
		},
		initComplete: function() {
			var dtSearch = $('#dt_RecentOrders_wrapper .dt-search').detach();
			$('#dt_RecentOrders_Search').append(dtSearch);
			$('#dt_RecentOrders_Search .dt-search').prepend('<i class="fi fi-rr-search"></i>');
			$('#dt_RecentOrders_Search .dt-search label').remove();
			$('#dt_RecentOrders_wrapper > .row.mt-2.justify-content-between').first().remove();
		},
		columnDefs: [{ 
			targets: [0],
			orderable: false,
		}]
	});
}

if ($('#dt_CategoriesList').length) {
	const dt_CategoriesList = $('#dt_CategoriesList').DataTable({
		searching: true,
		pageLength: 6,
		select: false,
		lengthChange: false,
		info: true,
		paging: true,
		language: {
			search: "",
			searchPlaceholder: 'Search',
			paginate: {
				previous: "<i class='fi fi-rr-angle-left'></i>",
				next: "<i class='fi fi-rr-angle-right'></i>",
				first: "<i class='fi fi-rr-angle-double-left'></i>",
				last: "<i class='fi fi-rr-angle-double-right'></i>"
			},
		},
		initComplete: function() {
			var dtSearch = $('#dt_CategoriesList_wrapper .dt-search').detach();
			$('#dt_CategoriesList_Search').append(dtSearch);
			$('#dt_CategoriesList_Search .dt-search').prepend('<i class="fi fi-rr-search"></i>');
			$('#dt_CategoriesList_Search .dt-search label').remove();
			$('#dt_CategoriesList_wrapper > .row.mt-2.justify-content-between').first().remove();
		},
		columnDefs: [{ 
			targets: [0],
			orderable: false,
		}]
	});
}

if ($('#dt_ProductList').length) {
	const dt_ProductList = $('#dt_ProductList').DataTable({
		searching: true,
		pageLength: 6,
		select: false,
		lengthChange: false,
		info: true,
		paging: true,
		language: {
			search: "",
			searchPlaceholder: 'Search',
			paginate: {
				previous: "<i class='fi fi-rr-angle-left'></i>",
				next: "<i class='fi fi-rr-angle-right'></i>",
				first: "<i class='fi fi-rr-angle-double-left'></i>",
				last: "<i class='fi fi-rr-angle-double-right'></i>"
			},
		},
		initComplete: function() {
			var dtSearch = $('#dt_ProductList_wrapper .dt-search').detach();
			$('#dt_ProductList_Search').append(dtSearch);
			$('#dt_ProductList_Search .dt-search').prepend('<i class="fi fi-rr-search"></i>');
			$('#dt_ProductList_Search .dt-search label').remove();
			$('#dt_ProductList_wrapper > .row.mt-2.justify-content-between').first().remove();
		},
	});
}

if ($('#dt_TransactionsHistory').length) {
	const dt_TransactionsHistory = $('#dt_TransactionsHistory').DataTable({
		searching: true,
		pageLength: 7,
		select: false,
		lengthChange: false,
		info: true,
		paging: true,
		language: {
			search: "",
			searchPlaceholder: 'Search',
			paginate: {
				previous: "<i class='fi fi-rr-angle-left'></i>",
				next: "<i class='fi fi-rr-angle-right'></i>",
				first: "<i class='fi fi-rr-angle-double-left'></i>",
				last: "<i class='fi fi-rr-angle-double-right'></i>"
			},
		},
		initComplete: function() {
			var dtSearch = $('#dt_TransactionsHistory_wrapper .dt-search').detach();
			$('#dt_TransactionsHistory_Search').append(dtSearch);
			$('#dt_TransactionsHistory_Search .dt-search').prepend('<i class="fi fi-rr-search"></i>');
			$('#dt_TransactionsHistory_Search .dt-search label').remove();
			$('#dt_TransactionsHistory_wrapper > .row.mt-2.justify-content-between').first().remove();
		},
	});
}

if ($('#dt_CustomerList').length) {
	const dt_CustomerList = $('#dt_CustomerList').DataTable({
		searching: true,
		pageLength: 6,
		select: false,
		lengthChange: false,
		info: true,
		paging: true,
		language: {
			search: "",
			searchPlaceholder: 'Search',
			paginate: {
				previous: "<i class='fi fi-rr-angle-left'></i>",
				next: "<i class='fi fi-rr-angle-right'></i>",
				first: "<i class='fi fi-rr-angle-double-left'></i>",
				last: "<i class='fi fi-rr-angle-double-right'></i>"
			},
		},
		initComplete: function() {
			var dtSearch = $('#dt_CustomerList_wrapper .dt-search').detach();
			$('#dt_CustomerList_Search').append(dtSearch);
			$('#dt_CustomerList_Search .dt-search').prepend('<i class="fi fi-rr-search"></i>');
			$('#dt_CustomerList_Search .dt-search label').remove();
			$('#dt_CustomerList_wrapper > .row.mt-2.justify-content-between').first().remove();
		},
		columnDefs: [{ 
			targets: [0],
			orderable: false,
		}]
	});
}



function trafficSourcesChartConfig() {
	const centerTextPlugin = {
		afterDraw(chart) {
			const { ctx, chartArea: { left, right, top, bottom } } = chart;
			const centerX = (left + right) / 2;
			const centerY = (top + bottom) / 2;

			// Calculate total dynamically
			const dataset = chart.data.datasets[0];
			const total = dataset.data.reduce((acc, val) => acc + val, 0);

			ctx.save();
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';

			// Total value
			ctx.font = 'bold 26px sans-serif';
			ctx.fillStyle = '#000';
			ctx.fillText(total, centerX, centerY - 5);

			// Label below
			ctx.font = '14px sans-serif';
			ctx.fillStyle = '#999';
			ctx.fillText('Sources', centerX, centerY + 18);

			ctx.restore();
		}
	};
	
	const canvas = document.getElementById('trafficSourcesChart');
    if (!canvas) return;
	const ctx = canvas.getContext('2d');
	
	new Chart(ctx, {
		type: 'doughnut',
		data: {
			labels: ['Direct', 'Google', 'Social Media', 'Referral', 'Campaigns'],
			datasets: [{
				data: [800, 700, 500, 600, 600],
				backgroundColor: ['#FD6A03', '#FE974F', '#FEB07A', '#FFCBA7', '#FFE9D9'],
				borderRadius: 10,
				spacing: 0, 
				hoverOffset: 5,
				borderWidth: 5,
				borderColor: '#fff',
				hoverBorderColor: '#fff'
			}]
		},
		options: {
			cutout: '65%',
			devicePixelRatio: 2,
			layout: {
				padding: 10
			},
			plugins: {
				legend: {
					display: false
				},
				tooltip: {
					callbacks: {
						label: context => `${context.label}: ${context.formattedValue}`
					}
				}
			}
		},
		plugins: [centerTextPlugin]

	});
}
document.addEventListener('DOMContentLoaded', trafficSourcesChartConfig);