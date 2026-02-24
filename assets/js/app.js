// assets/js/app.js

document.addEventListener("DOMContentLoaded", () => {
    lucide.createIcons();
    initApp();
});

const state = {
    currentProject: null,
    logs: [],
    projects: [
        { id: "all", name: "OVERVIEW / TODOS" },
        { id: "protocolosead_com", name: "SYS • PROTOCOLO SEAD" },
        { id: "estagiopaudosferros_com", name: "SYS • ESTÁGIO PDF" },
        { id: "sema_paudosferros", name: "SYS • SEMA PDF" },
        { id: "demutran_protocolosead_com", name: "SYS • DEMUTRAN SEAD" },
        { id: "demutranpaudosferros", name: "SYS • DEMUTRAN PDF" },
        { id: "suap2_estagiopaudosferros_com", name: "DB  • SUAP 2" },
        { id: "supaco_estagiopaudosferros_com", name: "DB  • SUPACO" },
        { id: "api_estagiopaudosferros_com", name: "API • ESTÁGIO" },
        { id: "api_protocolosead_com", name: "API • PROTOCOLO" },
    ],
    activeTab: "logs",
};

const DOM = {
    projectList: document.getElementById("project-list"),
    logsContainer: document.getElementById("logs-container"),
    projectTitle: document.getElementById("current-project-title"),

    // Tabs
    tabBtnDashboard: document.getElementById("tab-btn-dashboard"),
    tabBtnLogs: document.getElementById("tab-btn-logs"),

    // Views
    viewEmpty: document.getElementById("view-empty"),
    viewProject: document.getElementById("view-project"),
    tabContentDashboard: document.getElementById("tab-content-dashboard"),
    tabContentLogs: document.getElementById("tab-content-logs"),

    // Console
    searchInput: document.getElementById("search-input"),
    consoleEmpty: document.getElementById("console-empty"),
    consoleActive: document.getElementById("console-active"),
    consoleTitle: document.getElementById("console-title"),
    consoleBody: document.getElementById("console-body"),

    // Health e HUD Stats
    statTotalLogs: document.getElementById("stat-total-logs"),
    statTotalSize: document.getElementById("stat-total-size"),
    statErrors: document.getElementById("stat-errors"),
    statWarns: document.getElementById("stat-warns"),

    healthScore: document.getElementById("health-score"),
    healthStatus: document.getElementById("health-status"),
    healthRing: document.getElementById("health-ring"),

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
// INIT APP
// ==========================================
function initApp() {
    renderProjectsSidebar();

    DOM.tabBtnDashboard.addEventListener("click", () => switchTab("dashboard"));
    DOM.tabBtnLogs.addEventListener("click", () => switchTab("logs"));
    DOM.searchInput.addEventListener("input", () => setTimeout(renderLogCards, 300));
}

function renderProjectsSidebar() {
    DOM.projectList.innerHTML = state.projects
        .map(p => {
            const isActive = state.currentProject === p.id;
            let iconStr =
                p.id === "all"
                    ? "layout-grid"
                    : p.id.includes("DB")
                      ? "database"
                      : p.id.includes("API")
                        ? "cpu"
                        : "box";

            return `
        <button onclick="selectProject('${p.id}')" 
                class="nav-item w-full flex items-center gap-3 px-3 py-2.5 text-xs text-left font-tech tracking-wider uppercase
                ${isActive ? "active" : ""}">
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

    DOM.projectTitle.innerHTML = `<span class="opacity-50">TARGET:</span> ${proj ? proj.name : ""}`;

    DOM.viewEmpty.style.display = "none";
    DOM.viewProject.style.display = "flex";

    closeConsole();
    renderProjectsSidebar();

    switchTab("dashboard");
    loadLogsFromApi();
}

function switchTab(tabId) {
    state.activeTab = tabId;

    DOM.tabBtnDashboard.classList.toggle("active", tabId === "dashboard");
    DOM.tabBtnLogs.classList.toggle("active", tabId === "logs");

    DOM.tabContentDashboard.classList.toggle("active", tabId === "dashboard");
    DOM.tabContentLogs.classList.toggle("active", tabId === "logs");
}

// ==========================================
// API FETCH & CYBER DASHBOARD STATS
// ==========================================
async function loadLogsFromApi() {
    if (!state.currentProject) return;

    DOM.spinner.style.display = "flex";
    DOM.logsContainer.innerHTML =
        '<div class="text-center text-vs-dim text-xs font-tech p-4 glitch-text">INITIALIZING READ...</div>';

    try {
        const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
        const data = await response.json();

        if (data.data) {
            state.logs = data.data;
            updateDashboardStats();
            renderLogCards();
        } else {
            DOM.logsContainer.innerHTML = `<div class="text-xs text-[var(--neon-red)] p-3 text-center border border-[var(--neon-red)] bg-red-950/20">API EXCEPTION: ${data.error || "Nulo"}</div>`;
        }
    } catch (error) {
        console.error(error);
        DOM.logsContainer.innerHTML = `<div class="text-xs text-[var(--neon-red)] p-3 text-center border border-[var(--neon-red)] bg-red-950/20">NETWORK FAILURE</div>`;
    } finally {
        DOM.spinner.style.display = "none";
    }
}

function animateValue(obj, start, end, duration) {
    let startTimestamp = null;
    const step = timestamp => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

function updateDashboardStats() {
    DOM.statTotalLogs.textContent = state.logs.length;

    const totalBytes = state.logs.reduce((acc, log) => acc + log.size_bytes, 0);
    DOM.statTotalSize.textContent = formatBytes(totalBytes);

    let totalE = 0;
    let totalW = 0;

    state.logs.forEach(log => {
        const lower = (log.preview || "").toLowerCase();
        // Contagem rudimentar mas efetiva pros cards
        if (
            lower.includes("fatal error") ||
            lower.includes("uncaught error") ||
            lower.includes("doesn't exist") ||
            lower.includes("fail")
        )
            totalE++;
        if (lower.includes("warning") || lower.includes("notice")) totalW++;
    });

    DOM.statErrors.textContent = totalE;
    DOM.statWarns.textContent = totalW;

    // ===================================
    // SYSTEM HEALTH ALGORITHM (eDEX-UI TYPE)
    // ===================================
    let score = 100;
    score -= totalE * 15; // Erros sangram muito o score
    score -= totalW * 3; // Warnings dão pequenas beliscadas
    if (score < 0) score = 0;
    if (state.logs.length === 0) score = 100; // se ta vazio ta limpo

    DOM.healthScore.textContent = `${score}%`;
    animateValue(DOM.healthScore, 0, score, 800);

    // Aplicação das cores e status Hacker
    let statusTxt = "INTEGRITY: STABLE";
    let ringColor = "var(--neon-cyan)";
    DOM.healthRing.className = "w-32 h-32 rounded-full border-4 flex items-center justify-center pulse-glow";

    if (score < 90 && score >= 50) {
        statusTxt = "INTEGRITY: WARNING";
        ringColor = "var(--neon-orange)";
        DOM.healthRing.classList.remove("pulse-glow");
        DOM.healthRing.style.boxShadow = "0 0 15px var(--neon-orange)";
    } else if (score < 50) {
        statusTxt = "INTEGRITY: CRITICAL";
        ringColor = "var(--neon-red)";
        DOM.healthRing.className = "w-32 h-32 rounded-full border-4 flex items-center justify-center pulse-glow-red";
        DOM.healthScore.classList.add("text-[var(--neon-red)]", "glitch-text");
    } else {
        DOM.healthScore.classList.remove("text-[var(--neon-red)]", "glitch-text");
        DOM.healthRing.style.boxShadow = "";
    }

    DOM.healthStatus.textContent = statusTxt;
    DOM.healthRing.style.borderColor = ringColor;
}

// ==========================================
// RENDERIZAÇÃO DE LISTA DE LOGS E CONSOLE
// ==========================================
function renderLogCards() {
    let filtered = [...state.logs];
    const term = DOM.searchInput.value.toLowerCase().trim();
    if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

    if (filtered.length === 0) {
        DOM.logsContainer.innerHTML = `
            <div class="col-span-full text-center py-10 opacity-30 fade-in font-tech">
                <i data-lucide="scan" class="w-8 h-8 mx-auto mb-2"></i>
                <p class="text-xs">NO ASSETS FOUND IN DIRECTORY</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    DOM.logsContainer.innerHTML = filtered
        .map(log => {
            const previewLower = log.preview.toLowerCase();

            let stateObj = { icon: "file-json", border: "", glow: "" };

            if (
                previewLower.includes("fatal error") ||
                previewLower.includes("fail") ||
                previewLower.includes("doesn't exist")
            ) {
                stateObj = {
                    icon: "alert-triangle",
                    border: "border-l-2 border-l-[var(--neon-red)]",
                    glow: "text-[var(--neon-red)]",
                };
            } else if (previewLower.includes("warning")) {
                stateObj = {
                    icon: "alert-circle",
                    border: "border-l-2 border-l-[var(--neon-orange)]",
                    glow: "text-[var(--neon-orange)]",
                };
            } else {
                stateObj = {
                    icon: "file-text",
                    border: "border-l-2 border-l-transparent",
                    glow: "text-[var(--neon-cyan)]",
                };
            }

            return `
        <div onclick="openConsole('${encodeURIComponent(JSON.stringify(log))}')" class="log-card p-2 mb-1 flex flex-col gap-1.5 fade-in ${stateObj.border}">
            <div class="flex items-center gap-2">
                <i data-lucide="${stateObj.icon}" class="w-3.5 h-3.5 ${stateObj.glow} shrink-0"></i>
                <span class="text-xs font-mono truncate text-[#c9e0e5] font-semibold" title="${log.file}">${log.file}</span>
            </div>
            
            <div class="flex items-center justify-between text-[10px] font-tech text-[#4B798A]">
                <span class="flex items-center gap-1"><i data-lucide="hard-drive" class="w-3 h-3"></i> VOL: ${formatBytes(log.size_bytes)}</span>
                <span>${formatDate(log.modified)}</span>
            </div>
        </div>
    `;
        })
        .join("");

    lucide.createIcons();
}

// ==========================================
// VSCODE SYNTAX HIGHLIGHT ENGINE
// (FUNDO PRETO, TEXTO COLORIDO EM PT-BR)
// ==========================================
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
        rowClass += " row-critical js-has-error";
    } else if (lower.includes("warning") || lower.includes("notice") || lower.includes("syntax error")) {
        rowClass += " row-warning js-has-warning";
    } else if (lower.includes("mail") || lower.includes("smtp")) {
        rowClass += lower.includes("fail") || lower.includes("error") ? " row-critical js-has-error" : " row-mail";
    }

    let html = escapeHtml(lineRaw);

    // 1. Strings literais
    html = html.replace(/(&quot;.*?&quot;|&#039;.*?&#039;)/g, '<span class="hl-string">$1</span>');

    // 2. Variáveis de PHP ($variavel)
    html = html.replace(/(\$[A-Za-z0-9_]+)/g, '<span class="hl-var">$1</span>');

    // 3. Timestamps [Data Hora]
    html = html.replace(/(\[[0-9a-zA-Z :-]+(?:UTC|GMT|-0300|\\+0000)?\])/g, '<span class="hl-timestamp">$1</span>');

    // 4. Arquivos/Paths no servidor (ex: /home/domain/public_html/index.php)
    html = html.replace(
        /(\/home[A-Za-z0-9_.\/-]+\.php)\b/g,
        '<span class="hl-path" title="Acessar sub-diretório">$1</span>',
    );

    // 5. Palavras chave reservadas e tradução indireta/marcadores
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
    <div class="${rowClass}">
        <div class="log-num">${lineNum}</div>
        <div class="flex-1 whitespace-pre-wrap word-break-all">${html}</div>
    </div>`;
}

function openConsole(logStrEnc) {
    try {
        const log = JSON.parse(decodeURIComponent(logStrEnc));

        DOM.consoleEmpty.style.display = "none";
        DOM.consoleActive.style.display = "flex";

        DOM.consoleTitle.innerHTML = `<span class="opacity-30">READ //</span> ${log.file}`;

        let finalHtml = "";
        let hasErrors = false;

        if (log.preview) {
            const lines = log.preview.split("\\n");
            let idx = 1;
            for (let ln of lines) {
                const parsedLine = buildVscodeLine(ln, idx);
                if (parsedLine) {
                    finalHtml += parsedLine;
                    if (parsedLine.includes("js-has-error")) hasErrors = true;
                    idx++;
                }
            }
        }

        DOM.consoleBody.innerHTML =
            finalHtml || '<div class="px-4 py-2 font-tech text-[var(--neon-cyan)] italic">SYS: BUFFER EMPTY</div>';

        // Função moderna pedida pelo usuario: auto-scroll pra erro rapido!
        const actionsBar = document.getElementById("console-actions");
        if (hasErrors) {
            actionsBar.innerHTML = `
                <button onclick="scrollToError()" class="p-1 px-3 bg-[var(--neon-red)]/10 border border-[var(--neon-red)] text-xs font-tech text-[var(--neon-red)] rounded flex items-center gap-1 hover:bg-[var(--neon-red)] hover:text-black transition-all">
                    <i data-lucide="crosshair" class="w-3 h-3"></i> JUMP TO ERROR
                </button>
            `;
        } else {
            actionsBar.innerHTML = `<span class="text-xs font-tech text-[var(--neon-cyan)] opacity-60">NO CRITICAL TRIGGERS</span>`;
        }
        lucide.createIcons();
    } catch (e) {
        console.error("Erro ao abrir log", e);
    }
}

function scrollToError() {
    const errorLine = document.querySelector(".js-has-error");
    if (errorLine) {
        errorLine.scrollIntoView({ behavior: "smooth", block: "center" });
        // Efeito de piscar sutil pra ver onde parou
        setTimeout(() => {
            errorLine.style.backgroundColor = "rgba(255,0,0,0.3)";
            setTimeout(() => (errorLine.style.backgroundColor = ""), 500);
        }, 300);
    }
}

function closeConsole() {
    DOM.consoleActive.style.display = "none";
    DOM.consoleEmpty.style.display = "flex";
    DOM.consoleBody.innerHTML = "";
}

window.selectProject = selectProject;
window.loadLogsFromApi = loadLogsFromApi;
window.openConsole = openConsole;
window.closeConsole = closeConsole;
window.scrollToError = scrollToError;
