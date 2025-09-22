let growthChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // โหลดรายชื่อเด็ก
    loadStudentList();
    
    // เพิ่ม event listener สำหรับการเปลี่ยนประเภทกราฟ
    document.getElementById('chart_type').addEventListener('change', function() {
        const selectedType = this.value;
        toggleMeasurementFields(selectedType);
        if (document.getElementById('student_id').value) {
            loadGrowthData();
        }
    });

    // จัดการการส่งฟอร์ม
    document.getElementById('growthForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveGrowthData();
    });
});

// โหลดรายชื่อเด็ก
function loadStudentList() {
    fetch('../../api/get_students.php')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('student_id');
            select.innerHTML = '<option value="">เลือกเด็ก</option>';
            data.forEach(student => {
                select.innerHTML += `<option value="${student.id}">${student.firstname} ${student.lastname}</option>`;
            });
        });
}

// แสดง/ซ่อนฟิลด์ตามประเภทการวัด
function toggleMeasurementFields(type) {
    document.getElementById('inputForm').style.display = 'block';
    const weightField = document.querySelector('.weight-field');
    const heightField = document.querySelector('.height-field');
    const headField = document.querySelector('.head-field');

    weightField.style.display = type === 'weight' ? 'block' : 'none';
    heightField.style.display = type === 'height' ? 'block' : 'none';
    headField.style.display = type === 'head' ? 'block' : 'none';
}

// โหลดข้อมูลการเจริญเติบโต
function loadGrowthData() {
    const studentId = document.getElementById('student_id').value;
    const chartType = document.getElementById('chart_type').value;

    if (!studentId || !chartType) {
        Swal.fire('กรุณาเลือกเด็กและประเภทกราฟ', '', 'warning');
        return;
    }

    fetch(`../../api/get_growth_data.php?student_id=${studentId}&type=${chartType}`)
        .then(response => response.json())
        .then(data => {
            updateGrowthChart(data, chartType);
            updateGrowthTable(data);
        });
}

// อัพเดทกราฟ
function updateGrowthChart(data, type) {
    const ctx = document.getElementById('growthChart').getContext('2d');
    
    // ทำลายกราฟเดิมถ้ามี
    if (growthChart) {
        growthChart.destroy();
    }

    // สร้างข้อมูลสำหรับกราฟ
    const chartData = {
        labels: data.map(d => d.age_display),
        datasets: [
            {
                label: getChartLabel(type),
                data: data.map(d => getValueByType(d, type)),
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            },
            // เพิ่มเส้นเกณฑ์มาตรฐานตามประเภท
            ...getStandardLines(type)
        ]
    };

    // สร้างกราฟใหม่
    growthChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// อัพเดทตาราง
function updateGrowthTable(data) {
    const tbody = document.getElementById('growthTableBody');
    tbody.innerHTML = '';

    data.forEach(record => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(record.measurement_date)}</td>
            <td>${record.age_display}</td>
            <td class="weight-column">${record.weight || '-'}</td>
            <td class="height-column">${record.height || '-'}</td>
            <td class="head-column">${record.head_circumference || '-'}</td>
            <td>${record.growth_status}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteGrowthRecord(${record.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// บันทึกข้อมูล
function saveGrowthData() {
    const form = document.getElementById('growthForm');
    const formData = new FormData(form);
    formData.append('student_id', document.getElementById('student_id').value);
    formData.append('type', document.getElementById('chart_type').value);

    fetch('../../api/save_growth_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            Swal.fire('บันทึกสำเร็จ', '', 'success');
            loadGrowthData();
            form.reset();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
        }
    });
}

// ลบข้อมูล
function deleteGrowthRecord(id) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: "คุณต้องการลบข้อมูลนี้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../../api/delete_growth_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    Swal.fire('ลบสำเร็จ', '', 'success');
                    loadGrowthData();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', result.message, 'error');
                }
            });
        }
    });
}

// ฟังก์ชันช่วย
function getChartLabel(type) {
    switch(type) {
        case 'weight': return 'น้ำหนัก (กก.)';
        case 'height': return 'ส่วนสูง (ซม.)';
        case 'head': return 'เส้นรอบศีรษะ (ซม.)';
        default: return '';
    }
}

function getValueByType(record, type) {
    switch(type) {
        case 'weight': return record.weight;
        case 'height': return record.height;
        case 'head': return record.head_circumference;
        default: return null;
    }
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('th-TH', options);
}

// เส้นเกณฑ์มาตรฐาน
function getStandardLines(type) {
    // ตัวอย่างเส้นเกณฑ์มาตรฐาน (ต้องปรับตามข้อมูลจริง)
    return [
        {
            label: 'เกณฑ์สูงสุด',
            data: getStandardData(type, 'max'),
            borderColor: 'rgba(255, 99, 132, 0.5)',
            borderDash: [5, 5]
        },
        {
            label: 'เกณฑ์ต่ำสุด',
            data: getStandardData(type, 'min'),
            borderColor: 'rgba(54, 162, 235, 0.5)',
            borderDash: [5, 5]
        }
    ];
}

function getStandardData(type, level) {
    // ต้องกำหนดค่าตามเกณฑ์มาตรฐานจริง
    return [];
} 