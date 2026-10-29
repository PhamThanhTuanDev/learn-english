// Lịch từng tuần và từng ngày, dùng chung cho build.js (file Word) và export-json.js (dữ liệu cho web).

const { months, weeks, ieltsWeeks, finalWeeks } = require("./data");

const stageOf = (n) => (n <= 8 ? 1 : n <= 26 ? 2 : n <= 47 ? 3 : n <= 69 ? 4 : n <= 95 ? 5 : 6);
const monthOf = (n) => months.find((m) => n >= m[1] && n <= m[2]);
const isMonthEnd = (n) => months.some((m) => m[2] === n);
const VOCAB = {
  1: (v) => `8 từ mới/ngày – Oxford 3000 mức A1 (R19) + chủ đề “${v}”.`,
  2: (v) => `10 từ mới/ngày – Oxford 3000 mức A1–A2 + chủ đề “${v}”.`,
  3: (v) => `12 từ mới/ngày – Oxford 3000 mức B1 + chủ đề “${v}”.`,
  4: (v) => `15 từ mới/ngày – Oxford 5000 mức B2–C1 + collocations chủ đề “${v}”.`,
};
const SRC = { 1: "R1", 2: "R1", 3: "R2", 4: "R2 (cấu trúc nâng cao: R3)" };

function gateNote(n) {
  return isMonthEnd(n) ? ` Cuối tuần này: làm cổng kiểm tra tháng ${monthOf(n)[0]} (xem đầu tháng) và ghi kết quả vào Phụ lục C.` : "";
}

function standardDays(wk) {
  const st = stageOf(wk.n);
  const src = SRC[st];
  const days = [];
  if (wk.k) {
    days.push(["Kỹ năng – học", `Đọc mục “Ghi nhớ” ở trên; xem 1 bài mẫu tại ${st <= 2 ? "R7 (A2)" : st === 3 ? "R7 (B1)" : "R7 (B2)"} và đánh dấu cấu trúc trong bài mẫu.`]);
    days.push(["Kỹ năng – luyện", wk.d2]);
  } else {
    days.push([st === 4 ? "Ngôn ngữ B2 – học" : "Ngữ pháp – học", `Học chủ điểm ở dòng “Trọng tâm”. Tại ${src}: làm bài kiểm tra đầu bài → đọc giải thích → làm lại bài tập. Mục nào không có ở đó thì tra tên chủ điểm tại R4. Chép 3 câu ví dụ hay vào sổ.`]);
    days.push([st === 4 ? "Ngôn ngữ B2 – luyện" : "Ngữ pháp – luyện", `Viết 10 câu của riêng bạn dùng cấu trúc tuần, theo chủ đề “${wk.v}”; nhờ AI/gia sư kiểm tra (mẫu câu lệnh ở Phụ lục B). Chép câu sai và câu đã sửa vào sổ lỗi.`]);
  }
  days.push(["Nói", `${wk.s} Chuẩn bị 3–5 phút (chỉ ghi từ khóa, không viết cả bài), nói và ghi âm; nghe lại, ghi 3 lỗi.${st >= 2 ? " Có buổi nói với gia sư/bạn học trong tuần thì dùng chủ đề này." : ""}`]);
  days.push(["Viết (bản 1)", `${wk.w} Dùng ít nhất 5 từ mới và cấu trúc của tuần; gửi AI/gia sư sửa.`]);
  if (wk.ie) {
    days.push(["Làm quen IELTS", wk.ie]);
    days.push(["Sửa bài & kiểm tra", `Viết lại bài Ngày 4 thành bản 2 theo phản hồi. Tự làm lại bài tập ngôn ngữ của tuần không xem giải thích (mục tiêu ≥ 80%). Cập nhật sổ lỗi, chọn 3 lỗi ưu tiên.${gateNote(wk.n)}`]);
  } else {
    days.push(["Đầu ra tuần", `Sửa bài viết Ngày 4 thành bản 2 theo phản hồi; ghi âm lại bài nói Ngày 3 (bản 2) và so với bản 1. Lưu cả hai bản.${st === 1 ? " Đọc to bài viết bản 2, chú ý âm cuối." : ""}`]);
    days.push(["Kiểm tra & sửa lỗi", `Tự làm lại bài tập của tuần không xem giải thích (mục tiêu ≥ 80%; chưa đạt thì học lại Ngày 1–2 trước bài mới). Nghe lại 1 bài nghe trong tuần không transcript. Cập nhật sổ lỗi, chọn 3 lỗi ưu tiên.${gateNote(wk.n)}`]);
  }
  return days;
}

function ieltsDays(wk) {
  return [
    ["Listening", `${cap(wk.L)}: làm trong Cambridge IELTS (R27), đúng giờ, không dừng audio. Chấm, nghe lại câu sai với transcript; ghi nguyên nhân (chính tả, paraphrase, đổi ý, mất tập trung).`],
    ["Reading", `${cap(wk.R)}: làm đúng thời gian chuẩn (20 phút/passage). Với mỗi câu, ghi dòng bằng chứng và cặp từ paraphrase; phân tích mọi câu sai.`],
    ["Writing Task 1", `Task 1 – ${wk.W1}: viết trong 20 phút (từ 150 từ), có overview rõ. Sau đó 10–15 phút học: ${wk.lang}.`],
    ["Speaking", `${wk.S}: luyện với gia sư/bạn học hoặc AI; ghi âm. Nghe lại, chép 1 câu trả lời, nâng cấp từ vựng và ngữ pháp rồi nói lại.`],
    ["Writing Task 2", `Task 2 – dạng ${wk.W2}, chủ đề ${wk.topic.toLowerCase()}: 5 phút phân tích đề, 5 phút dàn ý, 30 phút viết (từ 250 từ). Gửi chấm theo 4 tiêu chí (R26).`],
    ["Sửa bài & sổ lỗi", `Viết lại Task 2 thành bản 2 theo phản hồi; làm lại các câu Listening/Reading sai mà không xem đáp án; cập nhật sổ lỗi; ghi 3 hành động cho tuần sau.${gateNote(wk.n)}`],
  ];
}

function mockDays(wk) {
  const k = wk.mock;
  return [
    ["Chuẩn bị", `Ôn sổ lỗi 30 phút; làm nhẹ 1 section Listening + 1 passage Reading. Chuẩn bị 1 đề Cambridge IELTS chưa làm, đồng hồ, máy ghi âm.`],
    ["Thi thử: L – R – W", `THI THỬ số ${k}: Listening (30') → Reading (60') → Writing (60') liên tục, không nghỉ, không tra từ. Buổi này dài khoảng 2 giờ 40 phút: gộp thời gian Ngày 2 và Ngày 3.`],
    ["Thi thử: Speaking", `THI THỬ số ${k}: Speaking 11–14 phút với gia sư/bạn học (hoặc AI đóng vai giám khảo); ghi âm. Chấm Listening/Reading bằng đáp án, đổi ra band; gửi 2 bài Writing đi chấm.`],
    ["Phân tích L – R", "Phân loại từng câu sai: từ vựng, paraphrase, chiến thuật, thời gian, mất tập trung. Đếm xem loại lỗi nào nhiều nhất."],
    ["Phân tích W – S", "Đối chiếu phản hồi Writing và bản ghi Speaking với 4 tiêu chí (R26); viết lại Task 2 thành bản 2; ghi 5 lỗi lớn nhất khi nói."],
    ["Tổng kết", `Ghi điểm từng kỹ năng vào Phụ lục C và so với cổng kiểm tra của tháng; chọn 2 kỹ năng yếu nhất làm trọng tâm tháng sau.`],
  ];
}

const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);

const REST_DAY = ["Nghỉ", "Nghỉ, hoặc ôn nhẹ 15–20 phút (Anki, nghe lại bài cũ). Có việc chưa xong thì làm bù hôm nay."];
const awlName = (awl) => (/^\d/.test(awl) ? "sublist " + awl : awl);

// Nội dung tuần n: tiêu đề, các dòng thông tin (highlight: true là ô ghi chú nổi bật) và 6 ngày học. Ngày 7 luôn là REST_DAY.
function weekPlan(n) {
  const st = stageOf(n);
  const details = [];
  const line = (label, text, highlight = false) => details.push({ label, text, highlight });
  if (st <= 4) {
    const wk = weeks.find((w) => w.n === n);
    if (wk.g) line("Trọng tâm", wk.g + ".");
    if (wk.note) line("Ghi nhớ", wk.note, true);
    line("Từ vựng", wk.v === "ôn thẻ khó" || wk.v.startsWith("ôn") ? "Không thêm thẻ mới; ôn thẻ khó và thẻ sai nhiều." : VOCAB[st](wk.v));
    if (wk.p && st <= 3) line("Phát âm", wk.p + (st === 3 ? ". Shadowing 5–10 câu từ bài nghe trong ngày." : "."));
    if (st <= 2) line("Nghe (VOA)", `Let’s Learn English Level 1 – Lesson ${2 * n - 1} và Lesson ${2 * n} (R9).`);
    return { title: wk.t, details, days: wk.days || standardDays(wk) };
  }
  if (st === 5) {
    const wk = ieltsWeeks.find((w) => w.n === n);
    if (wk.mock) {
      line("Từ vựng", `Ôn thẻ cũ; AWL ${awlName(wk.awl)} (giảm số thẻ mới trong tuần thi thử).`);
      return { title: `Thi thử hoàn chỉnh số ${wk.mock}`, details, days: mockDays(wk) };
    }
    line("Dạng bài tuần này", `Listening – ${wk.L} · Reading – ${wk.R} · Task 1 – ${wk.W1} · Task 2 – ${wk.W2} · Speaking – ${wk.S}.`);
    line("Từ vựng", `10 thẻ/ngày – AWL ${awlName(wk.awl)} (R20) + từ chủ đề “${wk.topic.toLowerCase()}”.`);
    return { title: wk.topic === "tự chọn" ? "Chữa dạng yếu nhất" : "Chủ đề " + wk.topic.toLowerCase(), details, days: ieltsDays(wk) };
  }
  const wk = finalWeeks.find((w) => w.n === n);
  if (wk.note) line("Lưu ý", wk.note, true);
  line("Từ vựng", n >= 101 ? "Chỉ ôn thẻ cũ, không thêm thẻ mới." : "Chỉ ôn thẻ cũ.");
  return { title: wk.mock ? "Thi thử hoàn chỉnh số " + wk.mock : wk.t, details, days: wk.mock ? mockDays(wk) : wk.days };
}

module.exports = { stageOf, monthOf, isMonthEnd, REST_DAY, weekPlan };
