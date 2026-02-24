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
    activeTab: "logs", // 'dashboard' ou 'logs'
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

    // Componentes da Aba Logs
    searchInput: document.getElementById("search-input"),
    consoleEmpty: document.getElementById("console-empty"),
    consoleActive: document.getElementById("console-active"),
    consoleTitle: document.getElementById("console-title"),
    consoleBody: document.getElementById("console-body"),

    // Estatísticas da Dashboard
    statTotalLogs: document.getElementById("stat-total-logs"),
    statTotalSize: document.getElementById("stat-total-size"),
    statErrors: document.getElementById("stat-errors"),
    statWarns: document.getElementById("stat-warns"),

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

    // Força ir pra tab de Resumo ao trocar projeto
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
// API FETCH E DASHBOARD
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
            DOM.logsContainer.innerHTML = `<div class="text-xs text-[#f44747] p-3 text-center">API Retornou Erro: ${data.error || "Nulo"}</div>`;
        }
    } catch (error) {
        console.error(error);
        DOM.logsContainer.innerHTML = `<div class="text-xs text-[#f44747] p-3 text-center">Falha de requisição. Verifique api/logs.php</div>`;
    } finally {
        DOM.spinner.style.display = "none";
    }
}

// Calcula estatísticas rápiadas baseadas nas previews de log para montar a Dashboard
function updateDashboardStats() {
    DOM.statTotalLogs.textContent = state.logs.length;

    const totalBytes = state.logs.reduce((acc, log) => acc + log.size_bytes, 0);
    DOM.statTotalSize.textContent = formatBytes(totalBytes);

    let totalE = 0;
    let totalW = 0;

    // Leitura rasa das previews de todos os arquivos do projeto
    state.logs.forEach(log => {
        const lower = (log.preview || "").toLowerCase();

        // Estima erros pelo preview cortado (nao e 100% preciso, mas util p dash)
        if (lower.includes("fatal error") || lower.includes("uncaught error")) totalE++;
        if (lower.includes("doesn't exist") || lower.includes("fail")) totalE++;

        if (lower.includes("warning") || lower.includes("notice")) totalW++;
    });

    DOM.statErrors.textContent = totalE > 0 ? `+${totalE} (Estimados)` : "Zero rastreados";
    DOM.statWarns.textContent = totalW > 0 ? `+${totalW} (Estimados)` : "Nenhum recente";
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
            <div class="col-span-full text-center py-10 text-vs-muted fade-in">
                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-30"></i>
                <p class="text-xs">Nenhum log corresponde ao filtro ou o projeto está vazio.</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }

    DOM.logsContainer.innerHTML = filtered
        .map(log => {
            const previewLower = log.preview.toLowerCase();
            const pathMatch = log.file.split("_")[1] || ""; // ex error_log_demutran.com

            // Determina se a preview do arquivo aponta algo critico
            let leftBorder = "border-transparent";
            if (previewLower.includes("fatal error") || previewLower.includes("fail")) leftBorder = "border-[#f44747]";
            else if (previewLower.includes("warning")) leftBorder = "border-[#d7ba7d]";

            return `
        <div onclick="openConsole('${encodeURIComponent(JSON.stringify(log))}')" class="log-card p-3 rounded-md flex flex-col gap-2 border-l-2 ${leftBorder} fade-in">
            <div class="flex items-center gap-2">
                <i data-lucide="file-json" class="w-4 h-4 text-[#ce9178] shrink-0"></i>
                <span class="text-sm font-medium text-[#c9d1d9] truncate" title="${log.file}">${log.file}</span>
            </div>
            
            <div class="flex items-center justify-between text-[11px] text-[#858585]">
                <span class="flex items-center gap-1"><i data-lucide="hard-drive" class="w-3 h-3"></i> ${formatBytes(log.size_bytes)}</span>
                <span>Últ. Mod: ${formatDate(log.modified)}</span>
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

    // Extra classes just for the left border marker, NOT full background
    let rowClass = "log-line";

    if (lower.includes("fatal error") || lower.includes("uncaught error") || lower.includes("doesn't exist")) {
        rowClass += " row-critical";
    } else if (lower.includes("warning") || lower.includes("notice") || lower.includes("syntax error")) {
        rowClass += " row-warning";
    } else if (lower.includes("mail") || lower.includes("smtp")) {
        rowClass += lower.includes("fail") || lower.includes("error") ? " row-critical" : " row-mail";
    }

    let html = escapeHtml(lineRaw);

    // 1. Strings literais -> Laranja/Verde
    html = html.replace(/(&quot;.*?&quot;|&#039;.*?&#039;)/g, '<span class="hl-string">$1</span>');

    // 2. Variáveis de PHP ($variavel) -> Azul claro
    html = html.replace(/(\$[A-Za-z0-9_]+)/g, '<span class="hl-var">$1</span>');

    // 3. Timestamps [Data Hora] -> Cinza Muted
    html = html.replace(/(\[[0-9a-zA-Z :-]+(?:UTC|GMT|-0300|\\+0000)?\])/g, '<span class="hl-timestamp">$1</span>');

    // 4. Arquivos/Paths no servidor (ex: /home/domain/public_html/index.php) -> Azul Link Escuro
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
        // Lookahead p garantir que n mexemos dentro do span ja pintado antes
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

        DOM.consoleTitle.textContent = log.file;

        // Faz o Parse Line by Line
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
            finalHtml || '<div class="px-4 py-2 text-vs-muted italic">Arquivo lido é infinito ou está vazio.</div>';
    } catch (e) {
        console.error("Erro ao abrir log", e);
    }
}

function closeConsole() {
    DOM.consoleActive.style.display = "none";
    DOM.consoleEmpty.style.display = "flex";
    DOM.consoleBody.innerHTML = "";
}

// Expõe globals pro HTML caso necessario
window.selectProject = selectProject;
window.loadLogsFromApi = loadLogsFromApi;
window.openConsole = openConsole;
window.closeConsole = closeConsole;
