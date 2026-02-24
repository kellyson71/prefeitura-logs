<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Logs // Dashboard Analítica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Tailwind Custom Config para cores VSCode-like -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        vs: {
                            bg: '#0d1117',        // Fundo princpal
                            panel: '#161b22',     // Paineis
                            border: '#30363d',    // Linhas
                            text: '#c9d1d9',      // Texto base
                            muted: '#8b949e',     // Texto secundario
                            blue: '#58a6ff',      // Ações/Links
                            green: '#3fb950',     // Sucesso/Strings
                            yellow: '#d29922',    // Avisos/Functions
                            red: '#f85149',       // Erros
                            purple: '#bc8cff',    // Keywords
                            cyan: '#39c5cf',      // Variaveis
                            hover: '#1f2428'      // Hover state
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['"Fira Code"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <style>
        body { 
            background-color: theme('colors.vs.bg');
            color: theme('colors.vs.text');
            overflow: hidden;
        }

        /* Scrollbars customizadas e discretas */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: theme('colors.vs.border'); 
            border: 2px solid theme('colors.vs.bg');
            border-radius: 6px; 
        }
        ::-webkit-scrollbar-thumb:hover { background: theme('colors.vs.muted'); }
        ::-webkit-scrollbar-corner { background: transparent; }

        /* Animações UI Suaves */
        .fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-right { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Classes e Utilidades de Botões/Paineis */
        .glass-panel {
            background: rgba(22, 27, 34, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid theme('colors.vs.border');
            border-radius: 0.75rem;
        }

        .nav-item {
            transition: all 0.2s ease;
            position: relative;
        }
        
        .nav-item.active {
            background: rgba(88, 166, 255, 0.1);
            color: theme('colors.vs.blue');
            font-weight: 600;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 10%; bottom: 10%; width: 3px;
            background: theme('colors.vs.blue');
            border-radius: 0 4px 4px 0;
        }

        /* 
         * SYNTAX HIGHLIGHTING CLASSES FOR LOG CONSOLE 
         */
        .log-line { 
            display: flex; 
            padding: 0 1rem; 
            min-height: 1.5rem;
            position: relative;
        }
        .log-line:hover { background-color: rgba(201, 209, 217, 0.05); }
        
        .log-num { 
            width: 3rem; 
            flex-shrink: 0; 
            text-align: right; 
            padding-right: 1rem; 
            color: theme('colors.vs.muted'); 
            user-select: none;
            border-right: 1px solid theme('colors.vs.border');
            margin-right: 1rem;
        }

        /* Colors based on regex matches */
        .hl-timestamp { color: theme('colors.vs.muted'); }
        .hl-path { color: theme('colors.vs.blue'); text-decoration: underline; text-decoration-color: transparent; transition: text-decoration-color 0.2s; cursor: pointer; }
        .hl-path:hover { text-decoration-color: theme('colors.vs.blue'); }
        .hl-string { color: theme('colors.vs.green'); }
        .hl-var { color: theme('colors.vs.cyan'); }
        .hl-keyword { color: theme('colors.vs.purple'); }
        .hl-func { color: theme('colors.vs.yellow'); }
        
        /* Severities */
        .hl-error { color: theme('colors.vs.red'); font-weight: 600; }
        .hl-warning { color: theme('colors.vs.yellow'); }
        
        /* Row Highlighting on critical */
        .row-critical { background: rgba(248, 81, 73, 0.1) !important; border-left: 3px solid theme('colors.vs.red'); }
        .row-warning { background: rgba(210, 153, 34, 0.05) !important; border-left: 3px solid theme('colors.vs.yellow'); }
        .row-mail { background: rgba(188, 140, 255, 0.05) !important; border-left: 3px solid theme('colors.vs.purple'); }

    </style>
</head>
<body class="flex h-screen overflow-hidden antialiased flex-col">

    <!-- Top Navigation Bar -->
    <header class="h-14 bg-vs-panel border-b border-vs-border shrink-0 flex items-center justify-between px-4 z-20">
        <div class="flex items-center gap-3">
            <div class="p-1.5 bg-vs-blue/10 rounded-lg">
                <i data-lucide="layers" class="w-5 h-5 text-vs-blue"></i>
            </div>
            <h1 class="text-sm font-semibold tracking-wide text-gray-200">Terminal Analítico</h1>
        </div>

        <div class="flex items-center gap-4">
            <div id="global-spinner" class="hidden items-center gap-2 text-xs font-medium text-vs-muted">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-vs-blue"></i> Lendo disco...
            </div>
            
            <div class="h-6 w-px bg-vs-border"></div>

            <!-- Botões Rápidos -->
            <button onclick="loadLogs()" class="text-vs-muted hover:text-vs-text transition-colors p-1.5 rounded-md hover:bg-white/5" title="Sincronizar">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
            
            <div class="flex items-center gap-2 pl-2 border-l border-vs-border">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-vs-blue to-vs-purple flex items-center justify-center text-xs font-bold text-white shadow-sm ring-2 ring-vs-bg">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <a href="logout.php" class="text-vs-muted hover:text-vs-red transition-colors ml-2" title="Sair do sistema">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- App Body -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar: Projects Menu -->
        <aside class="w-64 bg-[#090d13] border-r border-vs-border flex flex-col shrink-0">
            <div class="px-4 py-3 border-b border-vs-border bg-vs-panel/50">
                <h2 class="text-xs font-semibold text-vs-muted uppercase tracking-wider">Espaços de Trabalho</h2>
            </div>
            
            <nav class="flex-1 overflow-y-auto p-2 space-y-0.5" id="project-list">
                <!-- Rendered by JS -->
            </nav>
        </aside>

        <!-- Main Workspace Area -->
        <main class="flex-1 flex flex-col bg-vs-bg relative min-w-0" id="main-workspace">
            
            <!-- Estado Inicial: Dashboard None -->
            <div id="empty-state" class="absolute inset-0 flex flex-col items-center justify-center fade-in z-10">
                <div class="w-24 h-24 mb-6 relative">
                    <div class="absolute inset-0 bg-vs-blue/20 rounded-full blur-xl animate-pulse"></div>
                    <div class="relative bg-vs-panel border border-vs-border p-5 rounded-2xl shadow-xl">
                        <i data-lucide="activity" class="w-12 h-12 text-vs-blue"></i>
                    </div>
                </div>
                <h2 class="text-xl font-medium text-vs-text mb-2">Analisador de Logs</h2>
                <p class="text-vs-muted text-sm max-w-sm text-center">Selecione um espaço de trabalho na lateral para inspecionar os logs de sistema e aplicações.</p>
            </div>

            <!-- View: Lista de Logs do Projeto -->
            <div id="project-view" class="hidden flex-col h-full fade-in z-20 bg-vs-bg">
                
                <!-- File List Header Toolbar -->
                <div class="bg-vs-panel h-12 border-b border-vs-border flex items-center justify-between px-4 shrink-0">
                    <div class="flex items-center gap-2 text-sm text-vs-text">
                        <i data-lucide="folder-open" class="w-4 h-4 text-vs-blue"></i>
                        <span id="current-project-title" class="font-medium">Projeto</span>
                    </div>

                    <div class="relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-vs-muted"></i>
                        <input type="text" id="search-input" placeholder="Filtrar arquivos..." class="bg-[#090d13] border border-vs-border text-xs text-vs-text pl-8 pr-3 py-1.5 rounded-md outline-none focus:border-vs-blue focus:ring-1 focus:ring-vs-blue w-48 transition-all">
                    </div>
                </div>

                <!-- Painel Dividido (Lista de Arquivos vs Console Integrado) -->
                <div class="flex-1 flex overflow-hidden">
                    
                    <!-- Coluna Esquerda: Arquivos de Log -->
                    <div class="w-72 border-r border-vs-border bg-vs-panel/30 flex flex-col shrink-0 flex-none">
                        <div class="p-2 border-b border-vs-border text-[10px] font-bold text-vs-muted uppercase tracking-wide flex justify-between">
                            <span>Arquivos</span>
                            <span id="file-count">0</span>
                        </div>
                        <div class="flex-1 overflow-y-auto p-1.5 space-y-1" id="logs-container">
                            <!-- Cards de arquivos renderizados aqui -->
                        </div>
                    </div>

                    <!-- Coluna Direita: O "Console" em si, grande, focado na leitura -->
                    <div class="flex-1 flex flex-col min-w-0 bg-vs-bg relative">
                        
                        <!-- Empty Console State -->
                        <div id="console-empty" class="absolute inset-0 flex flex-col items-center justify-center text-vs-muted z-10 bg-vs-bg">
                            <i data-lucide="terminal-square" class="w-12 h-12 opacity-20 mb-3"></i>
                            <p class="text-sm">Selecione um arquivo ao lado para abrir o console.</p>
                        </div>

                        <!-- Console Ativo -->
                        <div id="console-active" class="hidden flex-col h-full z-20">
                            
                            <!-- Console File Tab -->
                            <div class="h-10 bg-[#090d13] border-b border-vs-border flex items-end px-2 gap-1 shrink-0 overflow-x-auto">
                                <!-- The "Tab" -->
                                <div class="bg-vs-bg border border-vs-border border-b-0 rounded-t-lg px-3 py-1.5 flex items-center gap-2 group min-w-0 max-w-xs relative shrink-0">
                                    <div class="w-2 h-2 rounded-full bg-vs-green"></div>
                                    <span id="console-filename" class="text-xs font-mono text-vs-text truncate">arquivo.log</span>
                                    <!-- Ação de fechar tab -->
                                    <button class="opacity-0 group-hover:opacity-100 hover:bg-white/10 rounded ml-1 transition-opacity" onclick="closeConsole()">
                                        <i data-lucide="x" class="w-3 h-3 text-vs-muted"></i>
                                    </button>
                                </div>
                                
                                <div class="ml-auto mb-1 flex items-center gap-1">
                                    <button onclick="copyConsoleOutput()" class="p-1 px-2 text-xs text-vs-muted hover:text-vs-text bg-white/5 hover:bg-white/10 border border-vs-border rounded flex items-center gap-1 transition-colors" id="btn-copy">
                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                        <span>Copiar</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Area do Código -->
                            <div class="flex-1 overflow-auto bg-vs-bg font-mono text-[13px] leading-relaxed py-2 selection:bg-vs-blue/30 relative" id="console-body">
                                <!-- Linhas parseadas em JS entram aqui -->
                            </div>
                            
                            <!-- Status Bar Base -->
                            <div class="h-7 bg-[#007acc] text-white text-[11px] flex items-center justify-between px-3 shrink-0 font-sans shadow-[0_-2px_10px_rgba(0,122,204,0.1)]">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center gap-1"><i data-lucide="rocket" class="w-3 h-3"></i> Pronto</span>
                                    <span class="flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3 text-white"></i> <span id="status-errs">0 Erros</span></span>
                                    <span class="flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3 text-white"></i> <span id="status-warns">0 Avisos</span></span>
                                </div>
                                <div class="flex items-center gap-4 text-white/80">
                                    <span id="status-size">0 KB</span>
                                    <span>UTF-8</span>
                                    <span>PHP</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Interface JavaScript -->
    <script>
        lucide.createIcons();

        const state = {
            currentProject: null,
            logs: [],
            projects: [
                { id: 'all', name: 'Todos os Projetos' },
                { id: 'protocolosead_com', name: 'Protocolo SEAD' },
                { id: 'estagiopaudosferros_com', name: 'Estágio PDF' },
                { id: 'sema_paudosferros', name: 'SEMA PDF' },
                { id: 'demutran_protocolosead_com', name: 'Demutran SEAD' },
                { id: 'demutranpaudosferros', name: 'Demutran PDF' },
                { id: 'suap2_estagiopaudosferros_com', name: 'SUAP 2 (DB)' },
                { id: 'supaco_estagiopaudosferros_com', name: 'Supaco (DB)' },
                { id: 'api_estagiopaudosferros_com', name: 'API Estágio' },
                { id: 'api_protocolosead_com', name: 'API Protocolo' }
            ]
        };

        const DOM = {
            projectList: document.getElementById('project-list'),
            logsContainer: document.getElementById('logs-container'),
            currentProjectTitle: document.getElementById('current-project-title'),
            searchInput: document.getElementById('search-input'),
            fileCount: document.getElementById('file-count'),
            
            views: {
                empty: document.getElementById('empty-state'),
                project: document.getElementById('project-view'),
                consoleEmpty: document.getElementById('console-empty'),
                consoleActive: document.getElementById('console-active'),
            },
            
            console: {
                filename: document.getElementById('console-filename'),
                body: document.getElementById('console-body'),
                statusErrs: document.getElementById('status-errs'),
                statusWarns: document.getElementById('status-warns'),
                statusSize: document.getElementById('status-size'),
            },

            spinner: document.getElementById('global-spinner')
        };

        const formatBytes = (bytes) => {
            if (bytes === 0) return '0 B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('pt-BR', {
                month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).format(date);
        };

        // Renderiza o menu lateral
        function renderProjects() {
            DOM.projectList.innerHTML = state.projects.map(p => {
                const isActive = state.currentProject === p.id;
                let iconStr = p.id === 'all' ? 'layout-grid' : (p.id.includes('db') ? 'database' : 'box');
                
                return `
                <button onclick="selectProject('${p.id}')" 
                        class="nav-item w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors text-left
                        ${isActive ? 'active' : 'text-vs-muted hover:text-vs-text hover:bg-white/5'}">
                    <i data-lucide="${iconStr}" class="w-4 h-4 shrink-0 opacity-80"></i>
                    <span class="truncate">${p.name}</span>
                </button>
            `}).join('');
            lucide.createIcons();
        }

        function selectProject(projectId) {
            state.currentProject = projectId;
            const proj = state.projects.find(p => p.id === projectId);
            
            DOM.currentProjectTitle.textContent = proj ? proj.name : 'Projeto';
            DOM.views.empty.classList.add('hidden');
            DOM.views.project.classList.remove('hidden');
            DOM.views.project.classList.add('flex');
            
            // Fecha console atual
            closeConsole();

            renderProjects();
            DOM.searchInput.value = ''; // Limpa busca
            loadLogs();
        }

        async function loadLogs() {
            if (!state.currentProject) return;

            DOM.spinner.classList.remove('hidden');
            DOM.spinner.classList.add('flex');
            DOM.logsContainer.innerHTML = '<div class="text-center text-vs-muted text-xs p-4"><i data-lucide="loader" class="w-4 h-4 animate-spin mx-auto mb-2"></i> Lendo disco...</div>';
            lucide.createIcons();

            try {
                const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
                const data = await response.json();
                
                if (data.data) {
                    state.logs = data.data;
                    renderLogs();
                } else {
                    DOM.logsContainer.innerHTML = `<div class="text-xs text-vs-red p-3 border border-vs-red/30 bg-vs-red/10 rounded mx-2">Erro API: ${data.error || 'Falha'}</div>`;
                }
            } catch (error) {
                console.error(error);
                DOM.logsContainer.innerHTML = `<div class="text-xs text-vs-red p-3 border border-vs-red/30 bg-vs-red/10 rounded mx-2">Falha de rede.</div>`;
            } finally {
                DOM.spinner.classList.add('hidden');
                DOM.spinner.classList.remove('flex');
            }
        }

        // Renderiza a lista de arquivos de log do lado esquerdo
        function renderLogs() {
            let filtered = [...state.logs];
            const term = DOM.searchInput.value.toLowerCase().trim();
            if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

            DOM.fileCount.textContent = filtered.length;

            if (filtered.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="text-center py-8 text-vs-muted">
                        <i data-lucide="file-warning" class="w-6 h-6 mx-auto mb-2 opacity-50"></i>
                        <p class="text-xs">Nenhum arquivo encontrado.</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            DOM.logsContainer.innerHTML = filtered.map((log) => {
                // Descobre se tem erros pelo preview (visual rápido na sidebar)
                const hasErr = log.preview.toLowerCase().includes('error') || log.preview.toLowerCase().includes('fail');
                const fileIconColor = hasErr ? 'text-vs-red' : 'text-vs-yellow';

                return `
                <button onclick="openConsole('${encodeURIComponent(JSON.stringify(log))}')" class="w-full bg-vs-bg border border-vs-border hover:border-vs-muted rounded-md p-2.5 flex flex-col gap-1.5 transition-colors text-left group">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 ${fileIconColor} shrink-0 opacity-80 group-hover:opacity-100"></i>
                            <span class="text-[13px] font-medium text-vs-text truncate" title="${log.file}">${log.file}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-vs-muted w-full px-5">
                        <span class="flex items-center gap-1"><i data-lucide="hard-drive" class="w-2.5 h-2.5"></i> ${formatBytes(log.size_bytes)}</span>
                        <span>${formatDate(log.modified)}</span>
                    </div>
                </button>
            `}).join('');

            lucide.createIcons();
        }

        // --- SYNTAX HIGHLIGHTING ENGINE ESTILO VSCODE ---
        // Funções para escapar e parsear o log recebido em visualização rica Colorida
        const escapeHtml = (unsafe) => unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");

        function highlightLogLine(line) {
            let html = escapeHtml(line);
            
            // 1. Strings em Aspas: "valor" ou 'valor' (Verde - Ponto crucial)
            html = html.replace(/(&quot;.*?&quot;|&#039;.*?&#039;)/g, '<span class="hl-string">$1</span>');

            // 2. Chaves de Array / Variáveis Nativas: $_POST, $_SESSION, $totalPorNome
            html = html.replace(/(\$[A-Za-z0-9_]+)/g, '<span class="hl-var">$1</span>');

            // 3. Timestamps: [05-Feb-2026 04:52:09 UTC]
            html = html.replace(/(\[[0-9]{2}-[a-zA-Z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} [A-Z]{3,4}\])/g, '<span class="hl-timestamp">$1</span>');

            // 4. Paths Unix (/home/...) com highlight especial no nome do arquivo
            html = html.replace(/(\/home[A-Za-z0-9_.\/-]+\/[A-Za-z0-9_.-]+\.php)\b/g, '<span class="hl-path" title="Path no servidor">$1</span>');

            // 5. Nomes de Função / Palavras conhecidas PHP: PHP Warning, PHP Parse error, Undefined array key
            const funcsAndKeywords = ['PHP Warning:', 'PHP Fatal error:', 'PHP Parse error:', 'Undefined array key', 'Undefined variable', 'syntax error', 'Stack trace:', 'thrown in'];
            for(let kw of funcsAndKeywords) {
                // Subistituimos ignorando aspas (que ja foram pintadas)
                let regex = new RegExp(`(${kw})(?![^<]*>|[^<>]*<\/span>)`, 'g');
                
                let cssClass = 'hl-keyword';
                if(kw.includes('Warning') || kw.includes('Undefined')) cssClass = 'hl-warning';
                if(kw.includes('Fatal') || kw.includes('Parse error') || kw.includes('thrown')) cssClass = 'hl-error';

                html = html.replace(regex, `<span class="${cssClass}">$1</span>`);
            }

            return html;
        }

        function parseToConsoleHTML(rawText) {
            if (!rawText) return { html: '<div class="text-vs-muted italic px-4">Arquivo sem conteúdo disponível...</div>', e:0, w:0 };

            let errCount = 0;
            let warnCount = 0;
            let finalHtml = '';
            
            // Ocultar marca final gerada caso seja tail cortado as vezes do servidor apache
            const lines = rawText.split('\\n');

            for (let i = 0; i < lines.length; i++) {
                const ln = lines[i];
                if (!ln.trim() && i === lines.length -1) continue; // ignora quebra final

                const lower = ln.toLowerCase();
                let rowWrapperClass = "log-line";

                // Classificação da linha para aplicar background de alerta
                if (lower.includes('fatal error') || lower.includes('parse error') || lower.includes('uncaught error') || lower.includes("doesn't exist")) {
                    rowWrapperClass += " row-critical";
                    errCount++;
                } 
                else if (lower.includes('warning') || lower.includes('notice')) {
                    rowWrapperClass += " row-warning";
                    warnCount++;
                }
                else if (lower.includes('mail') || lower.includes('smtp')) {
                    if (lower.includes('error') || lower.includes('fail')) {
                        rowWrapperClass += " row-critical";
                        errCount++;
                    } else {
                        rowWrapperClass += " row-mail";
                    }
                }

                // Processa a pintura linha a linha
                const pLine = highlightLogLine(ln);

                finalHtml += `
                <div class="${rowWrapperClass}">
                    <div class="log-num">${i+1}</div>
                    <div class="flex-1 whitespace-pre-wrap word-break-all">${pLine || '&nbsp;'}</div>
                </div>`;
            }

            return { html: finalHtml, e: errCount, w: warnCount };
        }

        function openConsole(logStrEnc) {
            try {
                const log = JSON.parse(decodeURIComponent(logStrEnc));
                
                DOM.views.consoleEmpty.classList.add('hidden');
                DOM.views.consoleActive.classList.remove('hidden');
                DOM.views.consoleActive.classList.add('flex');
                DOM.views.consoleActive.classList.add('slide-in-right');

                DOM.console.filename.textContent = log.file;
                DOM.console.statusSize.textContent = formatBytes(log.size_bytes);
                
                // Parseamento das informações e contadores
                const parsed = parseToConsoleHTML(log.preview);
                DOM.console.body.innerHTML = parsed.html;
                
                DOM.console.statusErrs.textContent = `${parsed.e} Erros`;
                DOM.console.statusWarns.textContent = `${parsed.w} Avisos`;
                
                // Piscar vermelho se for crítico
                if(parsed.e > 0) DOM.console.statusErrs.parentElement.classList.add('bg-vs-red', 'px-2', 'rounded-full');
                else DOM.console.statusErrs.parentElement.classList.remove('bg-vs-red', 'px-2', 'rounded-full');

                // Animar remover a class caso trocar de card rapidão
                setTimeout(() => DOM.views.consoleActive.classList.remove('slide-in-right'), 300);

            } catch(e) { console.error('Falha ao abrir log:', e); }
        }

        function closeConsole() {
            DOM.views.consoleEmpty.classList.remove('hidden');
            DOM.views.consoleActive.classList.add('hidden');
            DOM.views.consoleActive.classList.remove('flex');
            DOM.console.body.innerHTML = '';
        }

        function copyConsoleOutput() {
            // Pegamos apenas o texto, pulando a col de numeros
            let text = DOM.console.body.innerText;
            // Remover os numeros de linha iniciais de forma simples
            text = text.replace(/^[0-9]+[ \\t]*/gm, '');

            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="w-3 h-3 text-vs-green"></i> <span class="text-vs-green">Copiado</span>';
                btn.classList.add('border-vs-green/30', 'bg-vs-green/10');
                lucide.createIcons();
                
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('border-vs-green/30', 'bg-vs-green/10');
                    lucide.createIcons();
                }, 2000);
            }).catch(e => console.error('Erro de cópia', e));
        }

        // Eventos Globais
        DOM.searchInput.addEventListener('input', () => setTimeout(renderLogs, 300));

        // Start
        renderProjects();
    </script>
</body>
</html>
