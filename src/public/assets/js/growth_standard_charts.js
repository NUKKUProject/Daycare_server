// กราฟการเจริญเติบโตมาตรฐาน 4 แบบ (น้ำหนัก/ส่วนสูงตามอายุ, น้ำหนักตามส่วนสูง, เส้นรอบศีรษะ) ใช้ร่วมกันระหว่าง growth_history.php กับ view_child.php
// records: [{created_at, age_year, age_month, weight, height, head_circumference}]  ต้องโหลด Chart.js ก่อน

// เพิ่มฟังก์ชันสำหรับเตรียมข้อมูลกราฟ
function prepareChartData(type, records) {
    const sortedRecords = [...records].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    const labels = sortedRecords.map(r => new Date(r.created_at).toLocaleDateString('th-TH'));
    
    switch(type) {
        case 'weight':
            // ข้อมูลเส้นอ้างอิงสำหรับน้ำหนักตามเกณฑ์อายุ
            const weightReferenceLines = {
                plus2SD: [
                    {x: 0, y: 4.4}, {x: 6, y: 8.8}, {x: 12, y: 11.0},
                    {x: 18, y: 12.6}, {x: 24, y: 14.2}, {x: 30, y: 15.8},
                    {x: 36, y: 17.4}, {x: 42, y: 19.0}, {x: 48, y: 20.6},
                    {x: 54, y: 22.4}, {x: 60, y: 24.2}
                ],
                plus1_5SD: [
                    {x: 0, y: 4.2}, {x: 6, y: 8.4}, {x: 12, y: 10.4},
                    {x: 18, y: 11.8}, {x: 24, y: 13.4}, {x: 30, y: 14.8},
                    {x: 36, y: 16.2}, {x: 42, y: 17.6}, {x: 48, y: 19.0},
                    {x: 54, y: 20.6}, {x: 60, y: 22.2}
                ],
                median: [
                    {x: 0, y: 3.4}, {x: 6, y: 7.4}, {x: 12, y: 9.2},
                    {x: 18, y: 10.4}, {x: 24, y: 11.8}, {x: 30, y: 13.2},
                    {x: 36, y: 14.4}, {x: 42, y: 15.6}, {x: 48, y: 16.8},
                    {x: 54, y: 18.0}, {x: 60, y: 19.2}
                ],
                minus1_5SD: [
                    {x: 0, y: 2.8}, {x: 6, y: 6.4}, {x: 12, y: 8.0},
                    {x: 18, y: 9.2}, {x: 24, y: 10.4}, {x: 30, y: 11.6},
                    {x: 36, y: 12.8}, {x: 42, y: 13.8}, {x: 48, y: 14.8},
                    {x: 54, y: 15.8}, {x: 60, y: 16.8}
                ],
                minus2SD: [
                    {x: 0, y: 2.4}, {x: 6, y: 5.8}, {x: 12, y: 7.2},
                    {x: 18, y: 8.4}, {x: 24, y: 9.4}, {x: 30, y: 10.4},
                    {x: 36, y: 11.4}, {x: 42, y: 12.4}, {x: 48, y: 13.4},
                    {x: 54, y: 14.4}, {x: 60, y: 15.4}
                ]
            };

            // แปลงข้อมูลน้ำหนักของเด็กให้อยู่ในรูปแบบที่ต้องการ
            const weightData = sortedRecords.map(r => ({
                x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                y: r.weight
            }));

            return {
                labels: labels,
                datasets: [
                    {
                        label: '+2 SD (น้ำหนักมาก)',
                        data: weightReferenceLines.plus2SD,
                        borderColor: 'rgba(255, 0, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)'
                    },
                    {
                        label: '+1.5 SD (ค่อนข้างมาก)',
                        data: weightReferenceLines.plus1_5SD,
                        borderColor: 'rgba(7, 88, 44, 0.82)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(7, 88, 44, 0.1)'
                    },
                    {
                        label: 'Median (น้ำหนักตามเกณฑ์)',
                        data: weightReferenceLines.median,
                        borderColor: 'rgba(0, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)'
                    },
                    {
                        label: '-1.5 SD (ค่อนข้างน้อย)',
                        data: weightReferenceLines.minus1_5SD,
                        borderColor: 'rgba(65, 172, 88, 0.45)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(65, 172, 88, 0.1)'
                    },
                    {
                        label: '-2 SD (น้ำหนักน้อย)',
                        data: weightReferenceLines.minus2SD,
                        borderColor: 'rgba(255, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: true,
                        backgroundColor: 'rgba(255, 255, 0, 0.1)'
                    },
                    {
                        label: 'น้ำหนักของเด็ก',
                        data: weightData,
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: false
                    }
                ]
            };
        case 'height':
            // ข้อมูลเส้นอ้างอิงสำหรับส่วนสูงตามเกณฑ์อายุ
            const heightReferenceLines = {
                plus2SD: [
                    {x: 0, y: 52}, {x: 6, y: 70}, {x: 12, y: 80},
                    {x: 18, y: 86}, {x: 24, y: 92}, {x: 30, y: 97},
                    {x: 36, y: 102}, {x: 42, y: 107}, {x: 48, y: 112},
                    {x: 54, y: 117}, {x: 60, y: 122}
                ],
                plus1_5SD: [
                    {x: 0, y: 51}, {x: 6, y: 68}, {x: 12, y: 78},
                    {x: 18, y: 84}, {x: 24, y: 90}, {x: 30, y: 95},
                    {x: 36, y: 100}, {x: 42, y: 105}, {x: 48, y: 110},
                    {x: 54, y: 115}, {x: 60, y: 120}
                ],
                median: [
                    {x: 0, y: 50}, {x: 6, y: 67}, {x: 12, y: 76},
                    {x: 18, y: 82}, {x: 24, y: 88}, {x: 30, y: 93},
                    {x: 36, y: 98}, {x: 42, y: 103}, {x: 48, y: 108},
                    {x: 54, y: 113}, {x: 60, y: 118}
                ],
                minus1_5SD: [
                    {x: 0, y: 48}, {x: 6, y: 65}, {x: 12, y: 74},
                    {x: 18, y: 80}, {x: 24, y: 86}, {x: 30, y: 91},
                    {x: 36, y: 96}, {x: 42, y: 101}, {x: 48, y: 106},
                    {x: 54, y: 111}, {x: 60, y: 116}
                ],
                minus2SD: [
                    {x: 0, y: 47}, {x: 6, y: 64}, {x: 12, y: 72},
                    {x: 18, y: 78}, {x: 24, y: 84}, {x: 30, y: 89},
                    {x: 36, y: 94}, {x: 42, y: 99}, {x: 48, y: 104},
                    {x: 54, y: 109}, {x: 60, y: 114}
                ]
            };

            // แปลงข้อมูลส่วนสูงของเด็กให้อยู่ในรูปแบบที่ต้องการ
            const heightData = sortedRecords.map(r => ({
                x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                y: r.height
            }));

            return {
                labels: labels,
                datasets: [
                    {
                        label: '+2 SD (สูง)',
                        data: heightReferenceLines.plus2SD,
                        borderColor: 'rgba(255, 0, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)'
                    },
                    {
                        label: '+1.5 SD (ค่อนข้างสูง)',
                        data: heightReferenceLines.plus1_5SD,
                        borderColor: 'rgba(7, 88, 44, 0.82)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(7, 88, 44, 0.1)'
                    },
                    {
                        label: 'Median (ส่วนสูงตามเกณฑ์)',
                        data: heightReferenceLines.median,
                        borderColor: 'rgba(0, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)'
                    },
                    {
                        label: '-1.5 SD (ค่อนข้างเตี้ย)',
                        data: heightReferenceLines.minus1_5SD,
                        borderColor: 'rgba(65, 172, 88, 0.45)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(65, 172, 88, 0.1)'
                    },
                    {
                        label: '-2 SD (เตี้ย)',
                        data: heightReferenceLines.minus2SD,
                        borderColor: 'rgba(255, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: true,
                        backgroundColor: 'rgba(255, 255, 0, 0.1)'
                    },
                    {
                        label: 'ส่วนสูงของเด็ก',
                        data: heightData,
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: false
                    }
                ]
            };
        case 'weight_height':
            // ข้อมูลเส้นอ้างอิงสำหรับน้ำหนักตามเกณฑ์ส่วนสูง
            const referenceLines = {
                plus3SD: [
                        {x: 45, y: 3}, {x: 55, y: 6}, {x: 65, y: 9},
                        {x: 75, y: 12}, {x: 85, y: 15}, {x: 95, y: 18},
                        {x: 105, y: 21}, {x: 110, y: 24}
                    ],
                plus2SD: [
                        {x: 45, y: 2.8}, {x: 55, y: 5.5}, {x: 65, y: 8.2},
                        {x: 75, y: 11}, {x: 85, y: 14}, {x: 95, y: 17},
                        {x: 105, y: 20}, {x: 110, y: 22}
                    ],
                plus1_5SD: [  // เพิ่มเส้น +1.5 SD
                        {x: 45, y: 2.7}, {x: 55, y: 5.3}, {x: 65, y: 7.9},
                        {x: 75, y: 10.5}, {x: 85, y: 13.3}, {x: 95, y: 16},
                        {x: 105, y: 19}, {x: 110, y: 20.5}
                    ],
                median: [
                        {x: 45, y: 2.5}, {x: 55, y: 5}, {x: 65, y: 7.5},
                        {x: 75, y: 10}, {x: 85, y: 12.5}, {x: 95, y: 15},
                        {x: 105, y: 17.5}, {x: 110, y: 19}
                    ],
                minus1_5SD: [  // เพิ่มเส้น -1.5 SD
                        {x: 45, y: 2.3}, {x: 55, y: 4.7}, {x: 65, y: 7.1},
                        {x: 75, y: 9.5}, {x: 85, y: 11.7}, {x: 95, y: 14},
                        {x: 105, y: 16}, {x: 110, y: 17.5}
                    ],
                minus2SD: [
                        {x: 45, y: 2.2}, {x: 55, y: 4.5}, {x: 65, y: 6.8},
                        {x: 75, y: 9}, {x: 85, y: 11}, {x: 95, y: 13},
                        {x: 105, y: 15}, {x: 110, y: 16}
                    ]
            };

            return {
                labels: labels,
                datasets: [
                    // เส้นข้อมูลอ้างอิง
                    {
                        label: '+3 SD (อ้วน)',
                        data: referenceLines.plus3SD,
                        borderColor: 'rgba(255, 0, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)'
                    },
                    {
                        label: '+2 SD (เริ่มอ้วน)',
                        data: referenceLines.plus2SD,
                        borderColor: 'rgba(255, 165, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 165, 0, 0.1)'
                    },
                    {
                        label: '+1.5 SD (ท้วม)',  // เพิ่มเส้น +1.5 SD
                        data: referenceLines.plus1_5SD,
                        borderColor: 'rgba(7, 88, 44, 0.82)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 200, 0, 0.1)'
                    },
                    {
                        label: 'Median (สมส่วน)',
                        data: referenceLines.median,
                        borderColor: 'rgba(0, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)'
                    },
                    {
                        label: '-1.5 SD (ค่อนข้างผอม)',  // เพิ่มเส้น -1.5 SD
                        data: referenceLines.minus1_5SD,
                        borderColor: 'rgba(65, 172, 88, 0.45)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 255, 100, 0.1)'
                    },
                    {
                        label: '-2 SD (ผอม)',
                        data: referenceLines.minus2SD,
                        borderColor: 'rgba(255, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: true,
                        backgroundColor: 'rgba(255, 255, 0, 0.1)'
                    },
                    // ข้อมูลของเด็ก
                    {
                        label: 'น้ำหนักตามส่วนสูง',
                        data: sortedRecords.map(r => ({
                            x: r.height,
                            y: r.weight
                        })),
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: false
                    }
                ]
            };
        case 'head':
            // ข้อมูลเส้นอ้างอิงสำหรับเส้นรอบศีรษะตามเกณฑ์อายุ
            const headReferenceLines = {
                p97th: [ // เปอร์เซ็นไทล์ที่ 97
                    {x: 0, y: 36.6}, {x: 3, y: 41.5}, {x: 6, y: 44.0},
                    {x: 9, y: 45.8}, {x: 12, y: 47.0}, {x: 18, y: 48.4},
                    {x: 24, y: 49.2}, {x: 30, y: 49.8}, {x: 36, y: 50.2},
                    {x: 42, y: 50.4}, {x: 48, y: 50.6}, {x: 54, y: 50.8},
                    {x: 60, y: 51.0}
                ],
                p85th: [ // เปอร์เซ็นไทล์ที่ 85
                    {x: 0, y: 35.8}, {x: 3, y: 40.7}, {x: 6, y: 43.2},
                    {x: 9, y: 45.0}, {x: 12, y: 46.2}, {x: 18, y: 47.6},
                    {x: 24, y: 48.4}, {x: 30, y: 49.0}, {x: 36, y: 49.4},
                    {x: 42, y: 49.6}, {x: 48, y: 49.8}, {x: 54, y: 50.0},
                    {x: 60, y: 50.2}
                ],
                p50th: [ // เปอร์เซ็นไทล์ที่ 50
                    {x: 0, y: 35.0}, {x: 3, y: 40.0}, {x: 6, y: 42.5},
                    {x: 9, y: 44.3}, {x: 12, y: 45.5}, {x: 18, y: 46.9},
                    {x: 24, y: 47.7}, {x: 30, y: 48.3}, {x: 36, y: 48.7},
                    {x: 42, y: 48.9}, {x: 48, y: 49.1}, {x: 54, y: 49.3},
                    {x: 60, y: 49.5}
                ],
                p15th: [ // เปอร์เซ็นไทล์ที่ 15
                    {x: 0, y: 34.2}, {x: 3, y: 39.2}, {x: 6, y: 41.7},
                    {x: 9, y: 43.5}, {x: 12, y: 44.7}, {x: 18, y: 46.1},
                    {x: 24, y: 46.9}, {x: 30, y: 47.5}, {x: 36, y: 47.9},
                    {x: 42, y: 48.1}, {x: 48, y: 48.3}, {x: 54, y: 48.5},
                    {x: 60, y: 48.7}
                ],
                p3rd: [ // เปอร์เซ็นไทล์ที่ 3
                    {x: 0, y: 33.4}, {x: 3, y: 38.4}, {x: 6, y: 40.9},
                    {x: 9, y: 42.7}, {x: 12, y: 43.9}, {x: 18, y: 45.3},
                    {x: 24, y: 46.1}, {x: 30, y: 46.7}, {x: 36, y: 47.1},
                    {x: 42, y: 47.3}, {x: 48, y: 47.5}, {x: 54, y: 47.7},
                    {x: 60, y: 47.9}
                ]
            };

            // แปลงข้อมูลเส้นรอบศีรษะของเด็กให้อยู่ในรูปแบบที่ต้องการ
            const headData = sortedRecords.map(r => ({
                x: r.age_year * 12 + r.age_month, // แปลงอายุเป็นเดือน
                y: r.head_circumference
            }));

            return {
                datasets: [
                    {
                        label: 'มากกว่าเปอร์เซ็นไทล์ที่ 97',
                        data: headReferenceLines.p97th,
                        borderColor: 'rgba(255, 0, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 0, 0, 0.1)'
                    },
                    {
                        label: 'เปอร์เซ็นไทล์ที่ 85-97',
                        data: headReferenceLines.p85th,
                        borderColor: 'rgba(255, 165, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 165, 0, 0.1)'
                    },
                    {
                        label: 'เปอร์เซ็นไทล์ที่ 50-85',
                        data: headReferenceLines.p50th,
                        borderColor: 'rgba(0, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(0, 255, 0, 0.1)'
                    },
                    {
                        label: 'เปอร์เซ็นไทล์ที่ 15-50',
                        data: headReferenceLines.p15th,
                        borderColor: 'rgba(255, 255, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: '+1',
                        backgroundColor: 'rgba(255, 255, 0, 0.1)'
                    },
                    {
                        label: 'เปอร์เซ็นไทล์ที่ 3-15',
                        data: headReferenceLines.p3rd,
                        borderColor: 'rgba(255, 200, 0, 0.5)',
                        borderDash: [5, 5],
                        fill: true,
                        backgroundColor: 'rgba(255, 200, 0, 0.1)'
                    },
                    {
                        label: 'เส้นรอบวงศีรษะของเด็ก',
                        data: headData,
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: false
                    }
                ]
            };
    }
}

// เพิ่มฟังก์ชันสำหรับตั้งค่ากราฟ
function getChartOptions(type) {
    const options = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: true,
                text: getChartTitle(type)
            }
        }
    };

    if (type === 'weight') {
        options.scales = {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'อายุ (เดือน)'
                },
                min: 0,
                max: 60
            },
            y: {
                title: {
                    display: true,
                    text: 'น้ำหนัก (กก.)'
                },
                min: 0,
                max: 25
            }
        };
    }

    if (type === 'weight_height') {
            options.scales = {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    title: {
                        display: true,
                        text: 'ส่วนสูง (ซม.)'
                    },
                    min: 45,
                    max: 110
                },
                y: {
                    title: {
                        display: true,
                        text: 'น้ำหนัก (กก.)'
                    },
                min: 0,
                    max: 25
                }
            };
    }

    if (type === 'height') {
        options.scales = {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'อายุ (เดือน)'
                },
                min: 0,
                max: 60
            },
            y: {
                title: {
                    display: true,
                    text: 'ส่วนสูง (ซม.)'
                },
                min: 45,
                max: 125
            }
        };
    }

    if (type === 'head') {
        options.scales = {
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'อายุ (เดือน)'
                },
                min: 0,
                max: 60
            },
            y: {
                title: {
                    display: true,
                    text: 'เส้นรอบวงศีรษะ (ซม.)'
                },
                min: 32,
                max: 54
            }
        };
    }

    return options;
}

// เพิ่มฟังก์ชันสำหรับชื่อกราฟ
function getChartTitle(type) {
    switch(type) {
        case 'weight':
            return 'น้ำหนักตามเกณฑ์อายุ';
        case 'height':
            return 'ส่วนสูงตามเกณฑ์อายุ';
        case 'weight_height':
            return 'น้ำหนักตามเกณฑ์ส่วนสูง';
        case 'head':
            return 'เส้นรอบวงศีรษะ';
        default:
            return '';
    }
}
