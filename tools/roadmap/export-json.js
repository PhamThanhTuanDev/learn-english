// Xuất lộ trình thành JSON cho web. Chạy: node export-json.js ../../database/data/roadmap.json
// Không cần npm install: chỉ đọc data.js và schedule.js.

const fs = require("fs");
const { resources, stages, months } = require("./data");
const { stageOf, monthOf, REST_DAY, weekPlan } = require("./schedule");

const OUT = process.argv[2] || "roadmap.json";
const WEEKS = 104;

function check(ok, message) {
  if (!ok) throw new Error(message);
}

function minutes(text) {
  const m = /^(\d+)'$/.exec(text);
  check(m, `Thời lượng không đúng mẫu: ${text}`);
  return Number(m[1]);
}

months.forEach(([number, first], i) => {
  check(number === i + 1 && first === (i ? months[i - 1][2] + 1 : 1), `Tháng ${number} không nối tiếp tháng trước`);
});
check(months[months.length - 1][2] === WEEKS, `Tháng cuối phải kết thúc ở tuần ${WEEKS}`);

const data = {
  source: "Sinh từ tools/roadmap/data.js bằng tools/roadmap/export-json.js. Không sửa tay.",
  resources: resources.map(([code, name, source, usage]) => ({ code, name, source, usage })),
  stages: stages.map((s, i) => {
    const m = /^Giai đoạn (\d+) · (.+)$/.exec(s.title);
    check(m && Number(m[1]) === s.id && s.id === i + 1, `Tên giai đoạn không đúng mẫu: ${s.title}`);
    return {
      number: s.id,
      title: m[2],
      goal: s.goal,
      blocks: s.blocks.map(([title, time, description]) => ({ title, minutes: minutes(time), description })),
      notes: s.notes,
    };
  }),
  months: months.map(([number, firstWeek, lastWeek, stage, focus, gate]) => ({
    number, stage, first_week: firstWeek, last_week: lastWeek, focus, gate,
  })),
  weeks: [],
};

for (let n = 1; n <= WEEKS; n++) {
  const month = monthOf(n);
  check(month[3] === stageOf(n), `Tuần ${n}: giai đoạn của tháng ${month[0]} không khớp`);
  const { title, details, days } = weekPlan(n);
  check(days.length === 6, `Tuần ${n} có ${days.length} ngày học, cần 6`);
  data.weeks.push({
    number: n,
    month: month[0],
    stage: stageOf(n),
    title,
    details,
    days: [...days, REST_DAY].map(([label, task], i) => ({ number: (n - 1) * 7 + i + 1, day: i + 1, label, task, rest: i === 6 })),
  });
}

fs.writeFileSync(OUT, JSON.stringify(data, null, 2) + "\n");
const dayCount = data.weeks.reduce((sum, w) => sum + w.days.length, 0);
console.log(`written ${OUT}: ${data.stages.length} giai đoạn, ${data.months.length} tháng, ${data.weeks.length} tuần, ${dayCount} ngày`);
