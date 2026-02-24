// assets/js/app.js

document.addEventListener("DOMContentLoaded", () => {
    lucide.createIcons();
    initApp();
});

const state = {
    currentProject: null,
    logs: [],
    projects: [
        { id: "all", name: "Todos os Projetos" },
        { id: "protocolosead_com", name: "Protocolo SEAD" },
        { id: "estagiopaudosferros_com", name: "Estágio PDF" },
        { id: "sema_paudosferros", name: "SEMA PDF" },
        { id: "demutran_protocolosead_com", name: "Demutran SEAD" },
        { id: "demutranpaudosferros", name: "Demutran PDF" },
        { id: "suap2_estagiopaudosferros_com", name: "SUAP 2 (DB)" },
        { id: "supaco_estagiopaudosferros_com", name: "Supaco (DB)" },
        { id: "api_estagiopaudosferros_com", name: "API Estágio" },
        { id: "api_protocolosead_com", name: "API Protocolo" },
    ],
    activeTab: "logs",
    currentFilter: "all", // all | error | warn
};

const DOM = {
    projectList: document.getElementById("project-list"),
    logsContainer: document.getElementById("logs-container"),

    // Titulos e Tabs
    projectTitle: document.getElementById("current-project-title"),
    tabBtnDashboard: document.getElementById("tab-btn-dashboard"),
    tabBtnLogs: document.getElementById("tab-btn-logs"),

    // Views principais
    viewEmpty: document.getElementById("view-empty"),
    viewProject: document.getElementById("view-project"),

    // Conteudos de Tab
    tabContentDashboard: document.getElementById("tab-content-dashboard"),
    tabContentLogs: document.getElementById("tab-content-logs"),

    // Componentes da Aba Logs e Console
    searchInput: document.getElementById("search-input"),
    consoleEmpty: document.getElementById("console-empty"),
    consoleActive: document.getElementById("console-active"),
    consoleTitle: document.getElementById("console-title"),
    consoleBody: document.getElementById("console-body"),

    // Estatísticas da Dashboard e Banner Saúde
    statTotalLogs: document.getElementById("stat-total-logs"),
    statTotalSize: document.getElementById("stat-total-size"),
    statErrors: document.getElementById("stat-errors"),
    statWarns: document.getElementById("stat-warns"),

    healthBanner: document.getElementById("health-banner"),
    healthIconBg: document.getElementById("health-icon-bg"),
    healthIcon: document.getElementById("health-icon"),
    healthTitle: document.getElementById("health-title"),
    healthDesc: document.getElementById("health-desc"),

    spinner: document.getElementById("global-spinner"),
};

// Utils
const formatBytes = bytes => {
    if (bytes === 0) return "0 B";
    const k = 1024,
        sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const formatDate = dateString => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("pt-BR", {
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

// ==========================================
// INICIALIZAÇÃO E NAVEGAÇÃO MAIN
// ==========================================
function initApp() {
    renderProjectsSidebar();

    // Listeners de Tabs
    DOM.tabBtnDashboard.addEventListener("click", () => switchTab("dashboard"));
    DOM.tabBtnLogs.addEventListener("click", () => switchTab("logs"));

    DOM.searchInput.addEventListener("input", () => setTimeout(renderLogCards, 300));
}

function renderProjectsSidebar() {
    DOM.projectList.innerHTML = state.projects
        .map(p => {
            const isActive = state.currentProject === p.id;
            let iconStr = p.id === "all" ? "layout-grid" : p.id.includes("db") ? "database" : "box";

            return `
        <button onclick="selectProject('${p.id}')" 
                class="nav-item w-full flex items-center gap-3 px-3 py-2 text-sm text-left
                ${isActive ? "active" : "text-vs-muted"}">
            <i data-lucide="${iconStr}" class="w-4 h-4 shrink-0 opacity-80"></i>
            <span class="truncate">${p.name}</span>
        </button>
    `;
        })
        .join("");
    lucide.createIcons();
}

function selectProject(projectId) {
    state.currentProject = projectId;
    const proj = state.projects.find(p => p.id === projectId);

    DOM.projectTitle.textContent = proj ? proj.name : "Projeto Selecionado";

    DOM.viewEmpty.style.display = "none";
    DOM.viewProject.style.display = "flex";

    closeConsole();
    renderProjectsSidebar();

    switchTab("dashboard");
    loadLogsFromApi();
}

function switchTab(tabId) {
    state.activeTab = tabId;

    // Atualiza Botoes
    DOM.tabBtnDashboard.classList.toggle("active", tabId === "dashboard");
    DOM.tabBtnLogs.classList.toggle("active", tabId === "logs");

    // Atualiza Conteudo
    DOM.tabContentDashboard.classList.toggle("active", tabId === "dashboard");
    DOM.tabContentLogs.classList.toggle("active", tabId === "logs");
}

// ==========================================
// API FETCH E DASHBOARD (Health Status)
// ==========================================
async function loadLogsFromApi() {
    if (!state.currentProject) return;

    DOM.spinner.style.display = "flex";
    DOM.logsContainer.innerHTML = '<div class="text-center text-vs-muted text-xs p-4">Carregando...</div>';

    try {
        const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
        const data = await response.json();

        if (data.data) {
            state.logs = data.data;
            updateDashboardStats();
            renderLogCards();
        } else {
            DOM.logsContainer.innerHTML = `<div class="text-[11px] text-[#f44747] p-3 text-center">Nenhum dado retornado ou projeto vazio.</div>`;
            setHealthStatus("ok", "Nenhum log encontrado para ler.");
        }
    } catch (error) {
        console.error(error);
        DOM.logsContainer.innerHTML = `<div class="text-xs text-[#f44747] p-3 text-center">Falha de requisição. Verifique api/logs.php</div>`;
    } finally {
        DOM.spinner.style.display = "none";
    }
}

function setHealthStatus(level, message = "") {
    DOM.healthBanner.classList.remove(
        "hidden",
        "bg-[#1e2e22]",
        "bg-[#3d1d1d]",
        "bg-[#302717]",
        "border-[#3fb950]/30",
        "border-[#f44747]/30",
        "border-[#d7ba7d]/30",
    );
    DOM.healthIconBg.classList.remove("bg-[#3fb950]", "bg-[#f44747]", "bg-[#d7ba7d]", "animate-pulse");

    if (level === "ok") {
        DOM.healthBanner.classList.add("bg-[#1e2e22]", "border-[#3fb950]/30");
        DOM.healthIconBg.classList.add("bg-[#3fb950]");
        DOM.healthIcon.setAttribute("data-lucide", "check-circle");
        DOM.healthTitle.textContent = "Sistema Estável e Saudável";
        DOM.healthDesc.textContent =
            message || "Não detectamos erros de sintaxe ou alertas graves nas prévias recentes.";
    } else if (level === "warn") {
        DOM.healthBanner.classList.add("bg-[#302717]", "border-[#d7ba7d]/30");
        DOM.healthIconBg.classList.add("bg-[#d7ba7d]");
        DOM.healthIcon.setAttribute("data-lucide", "alert-triangle");
        DOM.healthTitle.textContent = "Sistema Estabilizado, Mas Requer Atenção";
        DOM.healthDesc.textContent =
            message || 'Há "Notices" ou "Warnings" do PHP sendo gerados. O sistema roda, mas pode haver lentidão.';
    } else if (level === "critical") {
        DOM.healthBanner.classList.add("bg-[#3d1d1d]", "border-[#f44747]/30");
        DOM.healthIconBg.classList.add("bg-[#f44747]", "animate-pulse");
        DOM.healthIcon.setAttribute("data-lucide", "siren");
        DOM.healthTitle.textContent = "Falha(s) Crítica(s) Detectada(s)!";
        DOM.healthDesc.textContent =
            message || "O projeto possui Erros Fatais ou Exceções recentes que provavelmente estão quebrando páginas.";
    }

    lucide.createIcons();
}

function countKeywords(textLines) {
    let errs = 0,
        warns = 0;
    const lower = (textLines || "").toLowerCase();
    const lines = lower.split("\\n");

    for (let i = 0; i < lines.length; i++) {
        if (
            lines[i].includes("fatal error") ||
            lines[i].includes("uncaught error") ||
            lines[i].includes("doesn't exist") ||
            lines[i].includes("parse error") ||
            lines[i].includes("smtp error")
        ) {
            errs++;
        } else if (lines[i].includes("warning") || lines[i].includes("notice")) {
            warns++;
        }
    }
    return { errs, warns };
}

function updateDashboardStats() {
    DOM.statTotalLogs.textContent = state.logs.length;

    const totalBytes = state.logs.reduce((acc, log) => acc + log.size_bytes, 0);
    DOM.statTotalSize.textContent = formatBytes(totalBytes);

    let totalE = 0;
    let totalW = 0;

    state.logs.forEach(log => {
        const counts = countKeywords(log.preview);
        totalE += counts.errs;
        totalW += counts.warns;
    });

    DOM.statErrors.textContent = totalE;
    DOM.statWarns.textContent = totalW;

    // Atualiza Banner Superior
    if (totalE > 0)
        setHealthStatus(
            "critical",
            `Encontramos ±${totalE} blocos de erro que requerem averiguação imetita no Console.`,
        );
    else if (totalW > 0)
        setHealthStatus("warn", `Existem ±${totalW} avisos de Notice e Variáveis ou Chaves sem definição.`);
    else setHealthStatus("ok");
}

// ==========================================
// RENDERIZAÇÃO DE LISTA C/ BADGES
// ==========================================
function renderLogCards() {
    let filtered = [...state.logs];
    const term = DOM.searchInput.value.toLowerCase().trim();
    if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

    if (filtered.length === 0) {
        DOM.logsContainer.innerHTML = `<div class="text-center py-6 text-vs-muted"><p class="text-[11px]">Vazio.</p></div>`;
        return;
    }

    DOM.logsContainer.innerHTML = filtered
        .map(log => {
            // Conta no pré-visualizador
            const counts = countKeywords(log.preview);

            let leftBorder = "border-transparent";
            let badgesHtml = "";

            if (counts.errs > 0) {
                leftBorder = "border-[#f44747]";
                badgesHtml += `<span class="bg-[#f44747]/20 text-[#f44747] text-[9px] px-1.5 py-0.5 rounded mr-1">${counts.errs} Err</span>`;
            }
            if (counts.warns > 0) {
                if (leftBorder === "border-transparent") leftBorder = "border-[#d7ba7d]";
                badgesHtml += `<span class="bg-[#d7ba7d]/20 text-[#d7ba7d] text-[9px] px-1.5 py-0.5 rounded mr-1">${counts.warns} Wrn</span>`;
            }

            return `
        <div onclick="openConsole('${encodeURIComponent(JSON.stringify(log))}')" class="log-card p-2.5 rounded flex flex-col gap-1.5 border-l-2 ${leftBorder} fade-in">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 min-w-0 pr-2">
                    <i data-lucide="file-json" class="w-3.5 h-3.5 text-[#ce9178] shrink-0"></i>
                    <span class="text-[12px] font-medium text-[#c9d1d9] truncate" title="${log.file}">${log.file}</span>
                </div>
                <!-- Mini Badges de Aviso -->
                <div class="flex shrink-0 ml-1">
                    ${badgesHtml}
                </div>
            </div>
            
            <div class="flex items-center justify-between text-[10px] text-[#858585]">
                <span class="flex items-center gap-1"><i data-lucide="hard-drive" class="w-2.5 h-2.5"></i> ${formatBytes(log.size_bytes)}</span>
                <span>Mod: ${formatDate(log.modified)}</span>
            </div>
        </div>
    `;
        })
        .join("");

    lucide.createIcons();
}

// ==========================================
// FILTROS E CONSOLE (AUTO SCROLL) E VSCODE ENGINE
// ==========================================
function setConsoleFilter(type) {
    state.currentFilter = type;

    // Atualiza Buttons Visuais
    document.getElementById("filter-all").className =
        `px-3 py-1.5 cursor-pointer ${type === "all" ? "text-white bg-[#37373d]" : "hover:bg-white/5 text-vs-muted"}`;
    document.getElementById("filter-error").className =
        `px-3 py-1.5 cursor-pointer flex items-center gap-1.5 ${type === "error" ? "text-white bg-[#37373d]" : "hover:bg-white/5 text-vs-muted"}`;
    document.getElementById("filter-warn").className =
        `px-3 py-1.5 cursor-pointer flex items-center gap-1.5 ${type === "warn" ? "text-white bg-[#37373d]" : "hover:bg-white/5 text-vs-muted"}`;

    // Aplica visibilidade via CSS nas linhas ativas
    const lines = DOM.consoleBody.querySelectorAll(".log-line");

    // Otimizando dom reflow
    DOM.consoleBody.style.display = "none";

    lines.forEach(line => {
        if (type === "all") {
            line.style.display = "flex";
        } else if (type === "error") {
            line.style.display = line.classList.contains("row-critical") ? "flex" : "none";
        } else if (type === "warn") {
            line.style.display = line.classList.contains("row-warning") ? "flex" : "none";
        }
    });

    DOM.consoleBody.style.display = "block";

    // Rolar p/ baixo caso mudou pro erro
    setTimeout(scrollToBottom, 50);
}

const escapeHtml = unsafe =>
    unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

function buildVscodeLine(lineRaw, lineNum) {
    if (!lineRaw.trim()) return "";

    let lower = lineRaw.toLowerCase();
    let rowClass = "log-line";

    if (lower.includes("fatal error") || lower.includes("uncaught error") || lower.includes("doesn't exist")) {
        rowClass += " row-critical";
    } else if (lower.includes("warning") || lower.includes("notice") || lower.includes("syntax error")) {
        rowClass += " row-warning";
    } else if (lower.includes("mail") || lower.includes("smtp")) {
        rowClass += lower.includes("fail") || lower.includes("error") ? " row-critical" : " row-mail";
    }

    // Se estivermos abrindo já filtrado, esconde aquilo que não bater com o filtro global
    let inlineStyle = "";
    if (state.currentFilter === "error" && !rowClass.includes("row-critical")) inlineStyle = "display:none;";
    else if (state.currentFilter === "warn" && !rowClass.includes("row-warning")) inlineStyle = "display:none;";

    let html = escapeHtml(lineRaw);

    html = html.replace(/(&quot;.*?&quot;|&#039;.*?&#039;)/g, '<span class="hl-string">$1</span>');
    html = html.replace(/(\$[A-Za-z0-9_]+)/g, '<span class="hl-var">$1</span>');
    html = html.replace(/(\[[0-9a-zA-Z :-]+(?:UTC|GMT|-0300|\\+0000)?\])/g, '<span class="hl-timestamp">$1</span>');
    html = html.replace(
        /(\/home[A-Za-z0-9_.\/-]+\.php)\b/g,
        '<span class="hl-path" title="Acessar sub-diretório">$1</span>',
    );

    const phpErrors = [
        { rgx: "PHP Warning:", class: "hl-warning" },
        { rgx: "PHP Fatal error:", class: "hl-error" },
        { rgx: "PHP Parse error:", class: "hl-error" },
        { rgx: "PHP Notice:", class: "hl-info" },
        { rgx: "Undefined array key", class: "hl-warning" },
        { rgx: "Undefined variable", class: "hl-warning" },
        { rgx: "syntax error", class: "hl-error" },
        { rgx: "Stack trace:", class: "hl-keyword" },
        { rgx: "thrown in", class: "hl-keyword" },
    ];

    for (let e of phpErrors) {
        let rule = new RegExp(`(${e.rgx})(?![^<]*>|[^<>]*<\/span>)`, "gi");
        html = html.replace(rule, `<span class="${e.class}">$1</span>`);
    }

    return `
    <div class="${rowClass}" style="${inlineStyle}">
        <div class="log-num">${lineNum}</div>
        <div class="flex-1 whitespace-pre-wrap word-break-all">${html}</div>
    </div>`;
}

function openConsole(logStrEnc) {
    try {
        const log = JSON.parse(decodeURIComponent(logStrEnc));

        DOM.consoleEmpty.style.display = "none";
        DOM.consoleActive.style.display = "flex";
        DOM.consoleTitle.textContent = log.file;

        let finalHtml = "";
        if (log.preview) {
            const lines = log.preview.split("\\n");
            let idx = 1;
            for (let ln of lines) {
                const parsedLine = buildVscodeLine(ln, idx);
                if (parsedLine) {
                    finalHtml += parsedLine;
                    idx++;
                }
            }
        }

        DOM.consoleBody.innerHTML =
            finalHtml || '<div class="px-4 py-2 text-vs-muted italic">Arquivo sem preview gerada.</div>';

        // Magica do Scroll Automatico apos abrir o Visualizador!
        setTimeout(scrollToBottom, 50);
    } catch (e) {
        console.error("Erro ao abrir log", e);
    }
}

function scrollToBottom() {
    DOM.consoleBody.scrollTop = DOM.consoleBody.scrollHeight;
}

function closeConsole() {
    DOM.consoleActive.style.display = "none";
    DOM.consoleEmpty.style.display = "flex";
    DOM.consoleBody.innerHTML = "";
}

function copyConsoleOutput() {
    // Para simplificar: apenas pega o innerText (que ja vem com as quebras e escondendo quem ta display:none!)
    // Depois retiramos o bloco de numeros de linhas que ficaria feio copiado.
    let text = DOM.consoleBody.innerText;
    text = text.replace(/^[0-9]+[ \\t]+/gm, "");

    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById("btn-copy");
        const originalHtml = btn.innerHTML;
        btn.innerHTML =
            '<i data-lucide="check" class="w-3.5 h-3.5 text-[#3fb950]"></i> <span class="text-[#3fb950]">Copiado!</span>';
        lucide.createIcons();

        setTimeout(() => {
            btn.innerHTML = originalHtml;
            lucide.createIcons();
        }, 1500);
    });
}

// Expõe globals
window.selectProject = selectProject;
window.loadLogsFromApi = loadLogsFromApi;
window.openConsole = openConsole;
window.closeConsole = closeConsole;
window.setConsoleFilter = setConsoleFilter;
window.scrollToBottom = scrollToBottom;
window.copyConsoleOutput = copyConsoleOutput;
