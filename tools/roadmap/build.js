const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, ShadingType,
  BorderStyle, AlignmentType, HeadingLevel, Footer, Header, PageNumber, ExternalHyperlink,
  LevelFormat, VerticalAlign,
} = require("docx");
const { resources, stages, months, weeks, ieltsWeeks, finalWeeks } = require("./data");

const OUT = process.argv[2] || "out.docx";
const FONT = "Calibri";
const W = 9638; // bề rộng nội dung A4, lề 2 cm
const C = {
  navy: "1F3864", blue: "2E75B6", grey: "595959", head: "D9E2F3", light: "F2F2F2",
  call: "EAF1FB", gate: "E2EFDA", note: "FFF2CC", stage: "1F3864", border: "B4C6E7",
};

// ---------- helpers ----------
function run(text, o = {}) {
  return new TextRun({ text, font: FONT, ...o });
}
function runs(content, base = {}) {
  if (!Array.isArray(content)) content = [content];
  return content.map((s) =>
    typeof s === "string" ? run(s, base) : run(s.t, { ...base, bold: s.b, italics: s.i, color: s.c || base.color, size: s.size || base.size })
  );
}
function para(content, o = {}) {
  return new Paragraph({
    children: runs(content, o.run || {}),
    spacing: { before: o.before ?? 0, after: o.after ?? 100, line: o.line },
    alignment: o.align,
    heading: o.heading,
    numbering: o.numbering,
    keepNext: o.keepNext,
    keepLines: o.keepLines,
    pageBreakBefore: o.pageBreakBefore,
    indent: o.indent,
  });
}
const bullet = (content, o = {}) => para(content, { ...o, numbering: { reference: "bullets", level: 0 }, after: o.after ?? 60 });
const h1 = (text, o = {}) => para(text, { heading: HeadingLevel.HEADING_1, keepNext: true, ...o });
const h2 = (text, o = {}) => para(text, { heading: HeadingLevel.HEADING_2, keepNext: true, ...o });
const h3 = (text, o = {}) => para(text, { heading: HeadingLevel.HEADING_3, keepNext: true, ...o });
const label = (lab, text, o = {}) => para([{ t: lab + ": ", b: true, c: C.navy }, text], { after: 60, keepNext: true, ...o });

const line = { style: BorderStyle.SINGLE, size: 4, color: C.border };
const BORDERS = { top: line, bottom: line, left: line, right: line, insideHorizontal: line, insideVertical: line };
const NO_LINE = { style: BorderStyle.NONE, size: 0, color: "FFFFFF" };

function cell(content, width, o = {}) {
  let children;
  if (Array.isArray(content) && content.length && content[0] instanceof Paragraph) children = content;
  else children = [para(content, { after: 0, run: { size: o.size || 19, ...(o.run || {}) }, align: o.align, keepNext: o.keepNext })];
  return new TableCell({
    children,
    width: { size: width, type: WidthType.DXA },
    columnSpan: o.span,
    verticalAlign: o.vAlign || VerticalAlign.TOP,
    shading: o.fill ? { fill: o.fill, type: ShadingType.CLEAR, color: "auto" } : undefined,
    margins: { top: 60, bottom: 60, left: 100, right: 100 },
  });
}

// rows: mảng các hàng; mỗi ô là nội dung hoặc { c, fill, span, run, align }
function table(widths, rows, o = {}) {
  const total = widths.reduce((a, b) => a + b, 0);
  return new Table({
    width: { size: total, type: WidthType.DXA },
    columnWidths: widths,
    borders: o.borders || BORDERS,
    rows: rows.map((r, i) => {
      const isHead = o.header && i === 0;
      let col = 0;
      const cells = r.map((c) => {
        const spec = c && typeof c === "object" && !Array.isArray(c) && "c" in c ? c : { c };
        const span = spec.span || 1;
        const width = widths.slice(col, col + span).reduce((a, b) => a + b, 0);
        col += span;
        return cell(spec.c, width, {
          span: spec.span,
          fill: isHead ? C.head : spec.fill,
          run: isHead ? { bold: true, color: C.navy } : spec.run,
          align: spec.align,
          size: o.size,
          // keepNext trên mọi hàng trừ hàng cuối giữ cả bảng trên một trang
          keepNext: o.keepTogether && i < rows.length - 1,
        });
      });
      return new TableRow({ children: cells, tableHeader: isHead, cantSplit: o.cantSplit !== false });
    }),
  });
}

// Hộp ghi chú một ô có nền màu
function box(children, fill) {
  return new Table({
    width: { size: W, type: WidthType.DXA },
    columnWidths: [W],
    borders: { top: NO_LINE, bottom: NO_LINE, left: { style: BorderStyle.SINGLE, size: 18, color: C.blue }, right: NO_LINE, insideHorizontal: NO_LINE, insideVertical: NO_LINE },
    rows: [new TableRow({ cantSplit: true, children: [new TableCell({ children, width: { size: W, type: WidthType.DXA }, shading: { fill, type: ShadingType.CLEAR, color: "auto" }, margins: { top: 100, bottom: 100, left: 160, right: 160 } })] })],
  });
}
const spacer = (after = 120, keepNext = false) => new Paragraph({ children: [], spacing: { before: 0, after }, keepNext });

function linkRuns(text) {
  if (!/https?:\/\//.test(text)) return [run(text, { size: 19 })];
  const parts = text.split("  ·  ");
  const out = [];
  parts.forEach((u, i) => {
    if (i) out.push(run("  ·  ", { size: 19 }));
    out.push(new ExternalHyperlink({ link: u.trim(), children: [run(u.trim(), { style: "Hyperlink", size: 19 })] }));
  });
  return out;
}

const CHECK = (keepNext = false) => [new Paragraph({ alignment: AlignmentType.CENTER, keepNext, children: [new TextRun({ text: "☐", font: "Segoe UI Symbol", size: 22 })] })];

// ---------- lịch tuần ----------
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

function dayTable(days) {
  const rows = [["Ngày", "Việc cụ thể", "Xong"]];
  days.forEach(([lab, text], i) => {
    rows.push([
      [para([{ t: `Ngày ${i + 1}`, b: true, c: C.navy, size: 19 }], { after: 0, keepNext: true }), para([{ t: lab, size: 17, c: C.grey }], { after: 0, keepNext: true })],
      text,
      { c: CHECK(true) },
    ]);
  });
  rows.push([
    { c: [para([{ t: "Ngày 7", b: true, c: C.navy, size: 19 }], { after: 0 }), para([{ t: "Nghỉ", size: 17, c: C.grey }], { after: 0 })], fill: C.light },
    { c: "Nghỉ, hoặc ôn nhẹ 15–20 phút (Anki, nghe lại bài cũ). Có việc chưa xong thì làm bù hôm nay.", fill: C.light },
    { c: CHECK(), fill: C.light },
  ]);
  return table([1500, 7538, 600], rows, { header: true, keepTogether: true });
}

function weekBlock(n) {
  const st = stageOf(n);
  const out = [];
  if (st <= 4) {
    const wk = weeks.find((w) => w.n === n);
    out.push(h3(`Tuần ${n} · ${wk.t}`));
    if (wk.g) out.push(label("Trọng tâm", wk.g + "."));
    if (wk.note) out.push(box([para([{ t: "Ghi nhớ: ", b: true }, wk.note], { after: 0, run: { size: 20 }, keepNext: true })], C.note), spacer(60, true));
    out.push(label("Từ vựng", wk.v === "ôn thẻ khó" || wk.v.startsWith("ôn") ? "Không thêm thẻ mới; ôn thẻ khó và thẻ sai nhiều." : VOCAB[st](wk.v)));
    if (wk.p && st <= 3) out.push(label("Phát âm", wk.p + (st === 3 ? ". Shadowing 5–10 câu từ bài nghe trong ngày." : ".")));
    if (st <= 2) out.push(label("Nghe (VOA)", `Let’s Learn English Level 1 – Lesson ${2 * n - 1} và Lesson ${2 * n} (R9).`));
    out.push(dayTable(wk.days || standardDays(wk)));
  } else if (st === 5) {
    const wk = ieltsWeeks.find((w) => w.n === n);
    if (wk.mock) {
      out.push(h3(`Tuần ${n} · Thi thử hoàn chỉnh số ${wk.mock}`));
      out.push(label("Từ vựng", `Ôn thẻ cũ; AWL ${/^\d/.test(wk.awl) ? "sublist " + wk.awl : wk.awl} (giảm số thẻ mới trong tuần thi thử).`));
      out.push(dayTable(mockDays(wk)));
    } else {
      out.push(h3(`Tuần ${n} · ${wk.topic === "tự chọn" ? "Chữa dạng yếu nhất" : "Chủ đề " + wk.topic.toLowerCase()}`));
      out.push(label("Dạng bài tuần này", `Listening – ${wk.L} · Reading – ${wk.R} · Task 1 – ${wk.W1} · Task 2 – ${wk.W2} · Speaking – ${wk.S}.`));
      out.push(label("Từ vựng", `10 thẻ/ngày – AWL ${/^\d/.test(wk.awl) ? "sublist " + wk.awl : wk.awl} (R20) + từ chủ đề “${wk.topic.toLowerCase()}”.`));
      out.push(dayTable(ieltsDays(wk)));
    }
  } else {
    const wk = finalWeeks.find((w) => w.n === n);
    out.push(h3(`Tuần ${n} · ${wk.mock ? "Thi thử hoàn chỉnh số " + wk.mock : wk.t}`));
    if (wk.note) out.push(box([para([{ t: "Lưu ý: ", b: true }, wk.note], { after: 0, run: { size: 20 }, keepNext: true })], C.note), spacer(60, true));
    out.push(label("Từ vựng", n >= 101 ? "Chỉ ôn thẻ cũ, không thêm thẻ mới." : "Chỉ ôn thẻ cũ."));
    out.push(dayTable(wk.mock ? mockDays(wk) : wk.days));
  }
  // Tuần cuối tháng không cần khoảng trống: tháng sau sang trang mới.
  if (!isMonthEnd(n)) out.push(spacer(200));
  return out;
}

// ---------- các phần của tài liệu ----------
const body = [];

// Trang bìa
body.push(spacer(1400));
body.push(para([{ t: "LỘ TRÌNH TIẾNG ANH", b: true, c: C.navy, size: 52 }], { align: AlignmentType.CENTER, after: 0 }));
body.push(para([{ t: "TỪ SỐ 0 ĐẾN IELTS 7.0+", b: true, c: C.navy, size: 52 }], { align: AlignmentType.CENTER, after: 240 }));
body.push(para([{ t: "Bản điều chỉnh · 24 tháng · 2 giờ/ngày · 6 ngày/tuần", c: C.blue, size: 30 }], { align: AlignmentType.CENTER, after: 120 }));
body.push(para([{ t: "Phát âm → A1 → A2 → B1 → B2 → IELTS Academic (C1)", c: C.grey, size: 22 }], { align: AlignmentType.CENTER, after: 80 }));
body.push(para([{ t: "Cập nhật: 28/10/2026 · Thay thế bản 18 tháng", c: C.grey, size: 20, i: true }], { align: AlignmentType.CENTER, after: 900 }));
body.push(box([
  para([{ t: "CÁCH DÙNG FILE NÀY", b: true, c: C.navy, size: 22 }], { after: 100 }),
  bullet("Đầu mỗi tháng: đọc trọng tâm và cổng kiểm tra của tháng.", { run: { size: 20 } }),
  bullet("Mỗi ngày: làm các khối cố định của giai đoạn (bảng ở đầu mỗi giai đoạn) + việc của ngày đó trong bảng tuần, rồi đánh dấu ô ☐.", { run: { size: 20 } }),
  bullet("Ngày 7 nghỉ hoặc ôn nhẹ. Cuối tháng làm cổng kiểm tra, ghi kết quả vào Phụ lục C.", { run: { size: 20 } }),
  bullet("Chưa qua cổng kiểm tra thì lặp lại tuần/tháng yếu; không chuyển giai đoạn chỉ vì đã hết thời gian.", { run: { size: 20 } }),
  bullet("Trong Word, bật View → Navigation Pane để nhảy nhanh tới từng giai đoạn, tháng, tuần.", { run: { size: 20 }, after: 0 }),
], C.call));

// Mục lục
body.push(para([{ t: "Mục lục", b: true, c: C.navy, size: 32 }], { pageBreakBefore: true, after: 160 }));
[
  "1. Điểm khác so với bản 18 tháng", "2. Mục tiêu và thời lượng", "3. Nguyên tắc học", "4. Khung 2 giờ mỗi ngày",
  "5. Kế hoạch từ vựng", "6. Phản hồi và luyện nói với người thật", "7. Bản đồ 24 tháng", "8. Danh mục nguồn học",
  "9. Lịch chi tiết theo tuần",
].forEach((t) => body.push(para(t, { after: 60, run: { size: 22 } })));
stages.forEach((s) => body.push(para(`${s.title} (${s.range})`, { after: 40, indent: { left: 440 }, run: { size: 20, color: C.grey } })));
["Phụ lục A. Sổ lỗi mẫu", "Phụ lục B. Mẫu câu lệnh cho AI", "Phụ lục C. Bảng theo dõi theo tháng", "Phụ lục D. Checklist hằng tuần và trước khi đăng ký thi", "Phụ lục E. Khi bị chậm tiến độ"]
  .forEach((t, i) => body.push(para(t, { after: 60, before: i === 0 ? 100 : 0, run: { size: 22 } })));

// 1
body.push(h1("1. Điểm khác so với bản 18 tháng", { pageBreakBefore: true }));
[
  "Kéo dài thành 24 tháng (104 tuần) ở mức 2 giờ/ngày. Muốn giữ 18 tháng cần học khoảng 3 giờ/ngày.",
  "Ngày nào cũng có từ vựng, nghe, đọc và phát âm/shadowing; chỉ khối “trọng tâm” đổi theo ngày. Bản cũ dồn nghe vào 1 ngày và đọc vào 1 ngày mỗi tuần.",
  "Từ vựng 8–15 từ/ngày theo danh sách chuẩn: Oxford 3000 → Oxford 5000 → Academic Word List, tổng khoảng 6.000 thẻ (bản cũ khoảng 1.200 từ).",
  "Thêm 8 tuần học 44 âm IPA ở đầu lộ trình; mỗi tuần đến tuần 47 có chủ điểm phát âm riêng (âm cuối, đuôi -s/-ed, trọng âm, nối âm, ngữ điệu).",
  "Có lịch phản hồi rõ ràng: AI sửa bài từ tháng 1, nói với người thật từ tháng 3, người có chuyên môn chữa Writing từ tháng 12.",
  "Cổng kiểm tra dùng đề mẫu chuẩn của Cambridge: A2 Key (tháng 6), B1 Preliminary (tháng 11), B2 First (tháng 16).",
  "Bổ sung ngữ pháp còn thiếu: quá khứ tiếp diễn, quá khứ hoàn thành và các cấu trúc B2–C1 (mệnh đề phân từ, câu chẻ, đảo ngữ, danh từ hóa, hedging, điều kiện hỗn hợp).",
  "Làm quen IELTS từ tháng 13, song song với B2. Giai đoạn IELTS dùng sách Cambridge IELTS Academic, tuần nào cũng luyện đủ 4 kỹ năng, viết 2 bài/tuần, có 9 lần thi thử hoàn chỉnh.",
  "Lịch từng ngày viết riêng cho từng tuần, bỏ các câu mẫu lặp lại không khớp nội dung.",
].forEach((t) => body.push(bullet(t)));

// 2
body.push(h1("2. Mục tiêu và thời lượng", { before: 240 }));
body.push(label("Mục tiêu", "IELTS Academic, Overall từ 7.0 trở lên.", { keepNext: false }));
body.push(para("Nếu bạn cần IELTS General Training (công việc, định cư): Listening và Speaking giống nhau; Reading và Writing Task 1 khác. Từ giai đoạn 5, thay phần luyện Reading và Task 1 bằng dạng General Training."));
body.push(para("Theo bảng quy đổi của IELTS, band 7.0 tương đương trình độ C1. Lộ trình vì vậy phải đưa bạn qua hết B2 và lên C1, không chỉ luyện dạng đề.", { keepNext: true }));
body.push(table([3000, 3000], [["Band IELTS", "Trình độ CEFR"], ["4.0 – 5.0", "B1"], ["5.5 – 6.5", "B2"], ["7.0 – 8.0", "C1"]], { header: true, size: 20 }));
body.push(spacer(120));
body.push(para("Thời lượng: 2 giờ × 6 ngày × 104 tuần ≈ 1.250 giờ. Cambridge ước tính khoảng 700–800 giờ học có hướng dẫn để đạt C1, chưa tính thời gian tự học; người tự học thường cần nhiều hơn. 24 tháng là mốc thực tế hơn 18 tháng, nhưng vẫn là mục tiêu cao."));
body.push(para("Lộ trình có cổng kiểm tra, không phải cam kết điểm. Chưa qua cổng thì kéo dài, không chuyển giai đoạn chỉ vì đã hết thời gian."));

// 3
body.push(h1("3. Nguyên tắc học", { before: 240 }));
[
  "Học 6 ngày/tuần; ngày 7 nghỉ hoặc ôn nhẹ 15–20 phút. Bỏ lỡ một ngày thì học tiếp từ ngày đó, không học dồn gấp đôi.",
  "Mỗi ngày làm đủ các khối cố định của giai đoạn (Phần 4) + việc của ngày trong bảng tuần. Đừng bỏ khối nghe–đọc để dồn thời gian cho ngữ pháp.",
  "Thẻ từ vựng: mặt trước là câu ví dụ có chỗ trống (hoặc nghĩa tiếng Việt), mặt sau là từ/cụm từ + phiên âm + audio. Học theo cụm (make a decision), không học từ đơn lẻ.",
  "Nghe 3 lượt: lượt 1 không transcript; lượt 2 trả lời câu hỏi hoặc ghi ý; lượt 3 đọc transcript, ghi chỗ nghe sai và nhại lại 5–10 câu.",
  "Đọc mở rộng chọn bài hiểu được khoảng 90% từ; đọc kỹ thì chọn bài khó hơn một chút. Không dịch từng từ.",
  "Viết và nói luôn có bản 1 → phản hồi → bản 2. Lưu cả hai bản để thấy tiến bộ. Không học thuộc bài mẫu.",
  "Sổ lỗi ghi mỗi ngày; cuối tuần chọn 3 lỗi ưu tiên để sửa trong tuần sau (mẫu ở Phụ lục A).",
  "Phản hồi từ người khác là bắt buộc (Phần 6). Tự chấm một mình thường không thấy được lỗi của chính mình.",
].forEach((t) => body.push(bullet(t)));

// 4
body.push(h1("4. Khung 2 giờ mỗi ngày", { before: 240 }));
body.push(para("Mỗi ngày gồm các khối cố định (làm hằng ngày) và một khối trọng tâm (đổi theo ngày). Đơn vị: phút. Có thể đổi thứ tự các khối trong ngày. Nguồn cụ thể cho từng khối nằm ở đầu mỗi giai đoạn trong Phần 9.", { keepNext: true }));
body.push(table([2438, 1200, 1200, 1200, 1200, 1200, 1200], [
  ["Giai đoạn", "Từ vựng", "Nghe", "Đọc", "Trọng tâm", "Phát âm / shadowing", "Sổ lỗi"],
  ["1 · Phát âm + A1", "15", "20", "15", "30", "30", "10"],
  ["2 · A1 → A2", "15", "25", "25", "30", "15", "10"],
  ["3 · B1", "15", "25", "25", "35", "10", "10"],
  ["4 · B2 + làm quen IELTS", "15", "25", "25", "35", "10", "10"],
  ["5 · IELTS + C1", "15", "15", "15", "55", "10", "10"],
  ["6 · Thi thử và thi", "15", "10", "10", "70", "–", "15"],
].map((r, i) => i === 0 ? r : r.map((c, j) => (j ? { c, align: AlignmentType.CENTER } : c))), { header: true, size: 20 }));
body.push(spacer(200));
body.push(para([{ t: "Khối trọng tâm theo ngày", b: true, c: C.navy, size: 22 }], { keepNext: true }));
body.push(table([1200, 4219, 4219], [
  ["Ngày", "Giai đoạn 1–4", "Giai đoạn 5"],
  ["Ngày 1", "Ngữ pháp / kỹ năng – học", "Listening theo dạng bài"],
  ["Ngày 2", "Ngữ pháp / kỹ năng – luyện", "Reading theo dạng bài"],
  ["Ngày 3", "Nói", "Writing Task 1 + ngôn ngữ C1"],
  ["Ngày 4", "Viết (bản 1)", "Speaking"],
  ["Ngày 5", "Đầu ra tuần (giai đoạn 4 từ tuần 53: làm quen IELTS)", "Writing Task 2"],
  ["Ngày 6", "Kiểm tra tuần, sửa lỗi", "Sửa bài, làm lại câu sai, sổ lỗi"],
  [{ c: "Ngày 7", fill: C.light }, { c: "Nghỉ / ôn nhẹ", fill: C.light }, { c: "Nghỉ / ôn nhẹ", fill: C.light }],
], { header: true, size: 20 }));
body.push(para("Tuần ôn tập, tuần thi thử và giai đoạn 6 có lịch riêng, ghi trực tiếp trong bảng của tuần đó.", { before: 80, run: { size: 20, color: C.grey, italics: true } }));

// 5
body.push(h1("5. Kế hoạch từ vựng", { before: 240 }));
body.push(table([1900, 1300, 4438, 2000], [
  ["Giai đoạn", "Từ mới/ngày", "Nguồn", "Tích lũy cuối giai đoạn"],
  ["1 · tuần 1–8", "8", "Oxford 3000 mức A1 (R19) + từ chủ đề tuần", "≈ 380"],
  ["2 · tuần 9–26", "10", "Oxford 3000 mức A1–A2", "≈ 1.450"],
  ["3 · tuần 27–47", "12", "Oxford 3000 mức B1", "≈ 2.950"],
  ["4 · tuần 48–69", "15", "Oxford 5000 mức B2–C1 + collocations", "≈ 4.950"],
  ["5 · tuần 70–95", "10", "Academic Word List (R20) + từ chủ đề IELTS + collocations", "≈ 6.500"],
  ["6 · tuần 96–104", "0", "Chỉ ôn thẻ cũ và sổ lỗi", "–"],
].map((r, i) => i === 0 ? r : [r[0], { c: r[1], align: AlignmentType.CENTER }, r[2], { c: r[3], align: AlignmentType.CENTER }]), { header: true, size: 20 }));
body.push(spacer(100));
[
  "Con số tích lũy là số thẻ đã học, không phải số từ nhớ chắc. Nghe–đọc nhiều mỗi ngày mới giúp từ ở lại lâu.",
  "Ưu tiên từ gặp trong bài nghe/đọc của tuần. Oxford 3000/5000 có ghi mức CEFR cho từng từ để chọn đúng mức.",
  "Nếu ôn thẻ cũ mất hơn 20 phút/ngày, giảm số từ mới trong vài ngày (không bỏ phần ôn).",
].forEach((t) => body.push(bullet(t)));

// 6
body.push(h1("6. Phản hồi và luyện nói với người thật", { before: 240 }));
body.push(table([1800, 3919, 3919], [
  ["Thời điểm", "Viết", "Nói"],
  ["Tháng 1–2", "AI sửa câu và đoạn ngắn (yêu cầu giải thích lỗi, không viết lại hộ).", "Ghi âm, so với audio mẫu. Nên có 1 buổi/tuần với gia sư để sửa âm."],
  ["Tháng 3–11", "AI hoặc gia sư sửa ít nhất 1 bài/tuần.", "1–2 buổi/tuần với người thật (gia sư online, câu lạc bộ, bạn học)."],
  ["Tháng 12–16", "Người có chuyên môn chữa ít nhất 2 tuần/lần; AI sửa các bài còn lại.", "2 buổi/tuần; từ tuần 59 luyện thêm theo dạng IELTS Speaking."],
  ["Tháng 17–24", "Task 2 được chấm theo 4 tiêu chí mỗi tuần; người có chuyên môn chấm ít nhất 2 tuần/lần.", "Thi thử Speaking với giáo viên mỗi tháng; các tuần còn lại luyện với bạn học/AI."],
], { header: true, size: 20 }));
body.push(spacer(100));
body.push(bullet("Mẫu câu lệnh cho AI ở Phụ lục B."));
body.push(bullet("Điểm band do AI ước lượng có thể lệch. Dùng AI để tìm lỗi, không dùng để kết luận band."));

// 7
body.push(h1("7. Bản đồ 24 tháng", { pageBreakBefore: true }));
body.push(para("Đọc bảng này đầu mỗi tháng. Lịch chi tiết từng tuần ở Phần 9.", { keepNext: true }));
const mapRows = [["Tháng", "Tuần", "Trọng tâm", "Cổng kiểm tra cuối tháng"]];
stages.forEach((s) => {
  mapRows.push([{ c: `${s.title} · ${s.range}`, span: 4, fill: C.call, run: { bold: true, color: C.navy } }]);
  months.filter((m) => m[3] === s.id).forEach((m) => mapRows.push([{ c: String(m[0]), align: AlignmentType.CENTER }, { c: `${m[1]}–${m[2]}`, align: AlignmentType.CENTER }, m[4], m[5]]));
});
body.push(table([800, 1000, 3700, 4138], mapRows, { header: true, size: 18 }));

// 8
body.push(h1("8. Danh mục nguồn học", { pageBreakBefore: true }));
body.push(para("Lịch tuần dùng các mã R0–R29. Khi một trang đổi giao diện, dùng ô tìm kiếm trên chính website đó với tên mục ghi ở đây.", { keepNext: true }));
body.push(new Table({
  width: { size: W, type: WidthType.DXA },
  columnWidths: [700, 2500, 3638, 2800],
  borders: BORDERS,
  rows: [
    new TableRow({ tableHeader: true, children: ["Mã", "Nguồn", "Đường dẫn", "Dùng khi"].map((h, j) => cell(h, [700, 2500, 3638, 2800][j], { fill: C.head, run: { bold: true, color: C.navy } })) }),
    ...resources.map(([code, name, url, use]) => new TableRow({
      cantSplit: true,
      children: [
        cell(code, 700, { run: { bold: true, color: C.navy } }),
        cell(name, 2500),
        new TableCell({ children: [new Paragraph({ children: linkRuns(url) })], width: { size: 3638, type: WidthType.DXA }, margins: { top: 60, bottom: 60, left: 100, right: 100 } }),
        cell(use, 2800),
      ],
    })),
  ],
}));
body.push(para("Phần lớn đường dẫn đã được mở thử ngày 28/10/2026. Website có thể đổi giao diện hoặc địa chỉ; khi đó tìm tên mục tương ứng trên cùng website.", { before: 100, run: { size: 18, color: C.grey, italics: true } }));

// 9
body.push(h1("9. Lịch chi tiết theo tuần", { pageBreakBefore: true }));
body.push(para("Mỗi giai đoạn mở đầu bằng bảng các khối cố định hằng ngày và nguồn dùng cho từng khối. Mỗi tháng có trọng tâm và cổng kiểm tra. Mỗi tuần có trọng tâm, từ vựng, phát âm (giai đoạn 1–3) và bảng việc cụ thể cho 7 ngày."));
stages.forEach((s) => {
  body.push(h1(s.title, { pageBreakBefore: s.id > 1, before: s.id > 1 ? 120 : 360 }));
  body.push(para([{ t: s.range, c: C.blue, b: true }], { after: 120 }));
  body.push(label("Mục tiêu giai đoạn", s.goal, { keepNext: false }));
  body.push(para([{ t: "Các khối cố định mỗi ngày", b: true, c: C.navy }], { before: 120, keepNext: true }));
  body.push(table([2200, 1000, 6438], [["Khối", "Thời gian", "Làm gì / nguồn"], ...s.blocks.map((b) => [b[0], { c: b[1], align: AlignmentType.CENTER }, b[2]])], { header: true, size: 19 }));
  if (s.notes.length) {
    body.push(spacer(80));
    s.notes.forEach((t) => body.push(bullet(t)));
  }
  months.filter((m) => m[3] === s.id).forEach((m, idx) => {
    body.push(h2(`Tháng ${m[0]} · Tuần ${m[1]}–${m[2]}`, { pageBreakBefore: idx > 0, before: idx === 0 ? 360 : 0 }));
    body.push(label("Trọng tâm tháng", m[4]));
    body.push(box([para([{ t: "Cổng kiểm tra cuối tháng: ", b: true, c: C.navy }, m[5]], { after: 0, run: { size: 20 } })], C.gate));
    body.push(spacer(160));
    for (let n = m[1]; n <= m[2]; n++) weekBlock(n).forEach((x) => body.push(x));
  });
});

// Phụ lục A
body.push(h1("Phụ lục A. Sổ lỗi mẫu", { pageBreakBefore: true }));
body.push(para("Có thể dùng sổ tay, Google Sheets hoặc Excel với các cột dưới đây. Mỗi lần một lỗi lặp lại, tăng số ở cột “Lần”. Lỗi lặp từ 3 lần trở lên là lỗi ưu tiên."));
body.push(para([{ t: "Loại lỗi: ", b: true }, "ngữ pháp · từ vựng/collocation · phát âm · chính tả · nghe nhầm · hiểu sai câu hỏi · liên kết/ý · quản lý thời gian."]));
const errRows = [["Ngày", "Câu / chỗ sai", "Sửa đúng", "Loại lỗi", "Lần"],
  [{ c: "12/03", run: { italics: true, color: C.grey } }, { c: "She go to work by bus.", run: { italics: true, color: C.grey } }, { c: "She goes to work by bus.", run: { italics: true, color: C.grey } }, { c: "Ngữ pháp – thiếu -s", run: { italics: true, color: C.grey } }, { c: "2", run: { italics: true, color: C.grey }, align: AlignmentType.CENTER }]];
for (let i = 0; i < 12; i++) errRows.push(["", "", "", "", ""]);
body.push(table([1100, 2900, 2900, 1638, 1100], errRows, { header: true, size: 20 }));

// Phụ lục B
body.push(h1("Phụ lục B. Mẫu câu lệnh cho AI", { pageBreakBefore: true }));
body.push(para("Dán vào ChatGPT, Claude hoặc công cụ AI bạn dùng; thay phần trong [ngoặc vuông]. AI giúp tìm lỗi nhanh, nhưng không thay được giáo viên ở các mốc quan trọng."));
[
  ["1. Sửa bài viết (giai đoạn 1–4)", "Bạn là giáo viên tiếng Anh. Trình độ của tôi khoảng [A2/B1/B2]. Hãy sửa bài viết dưới đây. Yêu cầu: (1) liệt kê từng lỗi thành bảng: câu gốc – câu đã sửa – giải thích ngắn bằng tiếng Việt – loại lỗi (ngữ pháp, từ vựng, chính tả, liên kết); (2) KHÔNG viết lại cả bài; (3) cuối cùng nêu 3 lỗi tôi cần ưu tiên sửa và 2 điểm tôi làm tốt. Bài viết: [dán bài]"],
  ["2. Kiểm tra câu tự đặt (Ngày 2)", "Tôi đang học [chủ điểm ngữ pháp]. Dưới đây là 10 câu tôi tự đặt. Với mỗi câu: cho biết đúng hay sai; nếu sai thì sửa và giải thích ngắn bằng tiếng Việt; nếu đúng nhưng chưa tự nhiên thì gợi ý cách nói tự nhiên hơn. Các câu: [dán câu]"],
  ["3. Luyện nói (Ngày 3, có thể dùng chế độ giọng nói)", "Hãy trò chuyện bằng tiếng Anh với tôi ở trình độ [A2/B1/B2], chủ đề [chủ đề Ngày 3]. Hỏi từng câu một và chờ tôi trả lời rồi mới hỏi tiếp; dùng câu ngắn, dễ hiểu. Sau 8 lượt, liệt kê lỗi ngữ pháp/từ vựng tôi đã mắc và gợi ý cách nói tự nhiên hơn."],
  ["4. Tạo bài kiểm tra tuần (Ngày 6)", "Tạo 20 câu trắc nghiệm về [chủ điểm của tuần] ở trình độ [A2/B1/B2], trộn nhiều dạng câu. Đặt đáp án và giải thích ở cuối, sau một dòng phân cách, để tôi không nhìn thấy trước."],
  ["5. Chấm IELTS Writing (giai đoạn 4–6)", "Hãy đóng vai giám khảo IELTS. Chấm bài Writing Task [1/2] dưới đây theo 4 tiêu chí chính thức: Task Achievement/Task Response, Coherence and Cohesion, Lexical Resource, Grammatical Range and Accuracy. Với mỗi tiêu chí: điểm ước lượng, 2 điểm mạnh, 2 điểm cần sửa kèm ví dụ cụ thể trong bài. Không viết lại bài hộ tôi. Đề bài: [dán đề] Bài viết: [dán bài]"],
  ["6. Đóng vai giám khảo Speaking (giai đoạn 5–6)", "Hãy đóng vai giám khảo IELTS Speaking. Hỏi tôi lần lượt Part 1 (4–5 câu), Part 2 (1 cue card, cho tôi 1 phút chuẩn bị) và Part 3 (4 câu), mỗi lần một câu. Kết thúc, nhận xét theo 4 tiêu chí: Fluency and Coherence, Lexical Resource, Grammatical Range and Accuracy, Pronunciation."],
].forEach(([title, prompt]) => {
  body.push(para([{ t: title, b: true, c: C.navy }], { before: 160, keepNext: true }));
  body.push(box([para(prompt, { after: 0, run: { size: 20 } })], C.light));
});

// Phụ lục C
body.push(h1("Phụ lục C. Bảng theo dõi theo tháng", { pageBreakBefore: true }));
body.push(para("Điền cuối mỗi tháng. Cột “Điều chỉnh” ghi quyết định: đi tiếp, lặp lại tuần nào, hoặc kéo dài giai đoạn."));
const trackRows = [["Tháng", "Tuần", "Số buổi", "Tổng giờ", "Kết quả cổng kiểm tra / thi thử", "Đạt?", "Điều chỉnh"]];
months.forEach((m) => trackRows.push([{ c: String(m[0]), align: AlignmentType.CENTER }, { c: `${m[1]}–${m[2]}`, align: AlignmentType.CENTER }, "", "", "", "", ""]));
body.push(table([750, 950, 950, 950, 3338, 750, 1950], trackRows, { header: true, size: 19 }));

// Phụ lục D
body.push(h1("Phụ lục D. Checklist", { pageBreakBefore: true }));
body.push(h2("Checklist hằng tuần"));
[
  "Ngày nào cũng có nghe và đọc (ít nhất 5/6 ngày).",
  "Anki ôn đủ mỗi ngày, không để dồn thẻ.",
  "Có ít nhất 1 bài viết và 1 bài nói được người khác/AI phản hồi.",
  "Đã viết bản 2 sau phản hồi và lưu cả hai bản.",
  "Đã làm kiểm tra Ngày 6 và biết rõ điều cần ôn.",
  "Sổ lỗi có 3 lỗi ưu tiên cho tuần sau.",
].forEach((t) => body.push(para([{ t: "☐  " }, t], { after: 80 })));
body.push(h2("Checklist trước khi đăng ký thi IELTS", { before: 240 }));
[
  "Đã xác định cần IELTS Academic hay General Training.",
  "Ít nhất 2 lần thi thử đầy đủ 4 kỹ năng gần nhất đạt mục tiêu, không kỹ năng nào dưới mức yêu cầu.",
  "Writing đã được người có chuyên môn chấm; biết rõ lỗi nào kéo điểm xuống.",
  "Speaking đã thi thử với giáo viên, có nhận xét theo 4 tiêu chí.",
  "Đã chọn thi trên máy hay trên giấy; nếu thi trên máy đã luyện gõ và làm bài làm quen trên máy.",
  "Đã đọc quy định, giấy tờ cần mang và giờ có mặt từ đơn vị tổ chức (R28).",
  "Đã biết chính sách thi lại (thi lại toàn bộ, hoặc One Skill Retake nếu đơn vị tổ chức có).",
].forEach((t) => body.push(para([{ t: "☐  " }, t], { after: 80 })));

// Phụ lục E
body.push(h1("Phụ lục E. Khi bị chậm tiến độ", { before: 360 }));
[
  "Kiểm tra tuần dưới 70% hoặc chưa tự làm được đầu ra: lặp lại tuần đó; không đi tiếp chỉ để đúng lịch.",
  "Một chủ điểm ngữ pháp vẫn sai sau 2 lần học: đổi nguồn giải thích (R4 hoặc sách R29), tự đặt 10 câu mới, nhờ AI kiểm tra rồi mới làm lại bài tập.",
  "Nghe không hiểu: chọn bài ngắn hơn hoặc thấp hơn một mức; đọc transcript trước rồi nghe lại.",
  "Không giữ được 2 giờ/ngày: giảm còn 90 phút nhưng giữ đủ 6 ngày/tuần. Cắt bớt khối trọng tâm trước, giữ nghe – đọc – Anki.",
  "Ôn Anki quá 20 phút/ngày: tạm dừng thẻ mới 3–5 ngày, chỉ ôn.",
  "Từ giai đoạn 5: không chạy theo số đề. Mỗi đề phải có phân tích lỗi và hành động sửa.",
  "Chậm hơn lịch 1–2 tháng là bình thường: dời ngày thi, không bỏ cổng kiểm tra.",
].forEach((t) => body.push(bullet(t)));
body.push(para("Lộ trình là kế hoạch tự học tham khảo, không thay thế đánh giá của giám khảo IELTS hoặc giáo viên.", { before: 240, run: { size: 18, color: C.grey, italics: true } }));

// ---------- document ----------
const doc = new Document({
  creator: "Lộ trình IELTS",
  title: "Lộ trình tiếng Anh từ số 0 đến IELTS 7.0+ (24 tháng)",
  styles: {
    default: { document: { run: { font: FONT, size: 21 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true, run: { font: FONT, size: 32, bold: true, color: C.navy }, paragraph: { spacing: { before: 120, after: 160 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true, run: { font: FONT, size: 27, bold: true, color: C.blue }, paragraph: { spacing: { before: 120, after: 120 }, outlineLevel: 1 } },
      { id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true, run: { font: FONT, size: 23, bold: true, color: C.navy }, paragraph: { spacing: { before: 120, after: 80 }, outlineLevel: 2 } },
    ],
  },
  numbering: {
    config: [{ reference: "bullets", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•", alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 360, hanging: 260 } } } }] }],
  },
  sections: [{
    properties: { page: { size: { width: 11906, height: 16838 }, margin: { top: 1134, bottom: 1134, left: 1134, right: 1134 } } },
    headers: { default: new Header({ children: [para([{ t: "Lộ trình tiếng Anh 0 → IELTS 7.0+ · 24 tháng", c: "8C8C8C", size: 16 }], { align: AlignmentType.RIGHT, after: 0 })] }) },
    footers: { default: new Footer({ children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [run("Trang ", { size: 16, color: "8C8C8C" }), new TextRun({ children: [PageNumber.CURRENT], font: FONT, size: 16, color: "8C8C8C" }), run(" / ", { size: 16, color: "8C8C8C" }), new TextRun({ children: [PageNumber.TOTAL_PAGES], font: FONT, size: 16, color: "8C8C8C" })] })] }) },
    children: body,
  }],
});

Packer.toBuffer(doc).then((buf) => {
  fs.writeFileSync(OUT, buf);
  console.log("written", OUT, buf.length, "bytes");
});
