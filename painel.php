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
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        vs: {
                            bg: '#0d1117',        // Fundo princpal
                            panel: '#161b22',     // Paineis e Cards
                            border: '#30363d',    // Linhas
                            text: '#c9d1d9',      // Texto base (claro)
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
            overflow-x: hidden;
            font-family: 'Inter', sans-serif;
        }

        /* Scrollbars customizadas */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: theme('colors.vs.bg'); }
        ::-webkit-scrollbar-thumb { 
            background: theme('colors.vs.border'); 
            border-radius: 4px; 
        }
        ::-webkit-scrollbar-thumb:hover { background: theme('colors.vs.muted'); }

        .fade-in { animation: fadeIn 0.3s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Estilos do Modal de Log (VSCode Theme) */
        .log-line { 
            display: flex; 
            padding: 0 1rem; 
            min-height: 1.5rem;
            color: theme('colors.vs.text'); /* Força texto claro */
            font-family: 'Fira Code', monospace;
            font-size: 13px;
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

        /* Syntax Highlighting Colors */
        .hl-timestamp { color: theme('colors.vs.muted'); }
        .hl-path { color: theme('colors.vs.blue'); text-decoration: underline; text-decoration-color: transparent; transition: text-decoration-color 0.2s; cursor: pointer; }
        .hl-path:hover { text-decoration-color: theme('colors.vs.blue'); }
        .hl-string { color: theme('colors.vs.green'); }
        .hl-var { color: theme('colors.vs.cyan'); }
        .hl-keyword { color: theme('colors.vs.purple'); }
        .hl-func { color: theme('colors.vs.yellow'); }
        
        .hl-error { color: theme('colors.vs.red'); font-weight: 600; }
        .hl-warning { color: theme('colors.vs.yellow'); }
        
        /* Row Highlighting no fundo (muito sutil para não ofuscar o texto) */
        .row-critical { background: rgba(248, 81, 73, 0.05) !important; border-left: 3px solid theme('colors.vs.red'); }
        .row-warning { background: rgba(210, 153, 34, 0.05) !important; border-left: 3px solid theme('colors.vs.yellow'); }
        .row-mail { background: rgba(188, 140, 255, 0.05) !important; border-left: 3px solid theme('colors.vs.purple'); }

        .glass-panel {
            background: rgba(22, 27, 34, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid theme('colors.vs.border');
            border-radius: 0.75rem;
        }

        .card-hover:hover {
            border-color: theme('colors.vs.muted');
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="flex h-screen flex-col">

    <!-- Navbar -->
    <header class="h-16 bg-vs-panel border-b border-vs-border flex items-center justify-between px-6 z-20 shrink-0 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-vs-blue/10 rounded-lg border border-vs-blue/20 shadow-[0_0_10px_rgba(88,166,255,0.1)]">
                <i data-lucide="terminal-square" class="w-5 h-5 text-vs-blue"></i>
            </div>
            <div>
                <h1 class="text-base font-bold tracking-wide text-white">SysLog <span class="font-normal text-vs-muted">// Dashboard Central</span></h1>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button onclick="loadLogs()" class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-vs-text bg-vs-bg border border-vs-border rounded-md hover:border-vs-muted hover:bg-vs-hover transition-all focus:outline-none">
                <i data-lucide="refresh-cw" class="w-4 h-4" id="icon-refresh"></i>
                <span class="hidden sm:inline">Sincronizar</span>
            </button>
            <div class="h-6 w-px bg-vs-border mx-2"></div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-vs-blue to-vs-purple flex items-center justify-center text-sm font-bold text-white shadow-md">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div class="hidden sm:block">
                    <p class="text-xs font-semibold text-white"><?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p class="text-[10px] text-vs-muted uppercase tracking-wider">Administrador</p>
                </div>
                <a href="logout.php" class="p-1.5 text-vs-muted hover:text-vs-red transition-colors rounded-md hover:bg-white/5 ml-1" title="Sair">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-vs-bg border-r border-vs-border flex flex-col shrink-0">
            <div class="px-5 py-3 text-xs font-bold text-vs-muted uppercase tracking-wider border-b border-vs-border">
                Projetos Monitorados
            </div>
            
            <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-1" id="project-list">
                <!-- JS Render -->
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-[#090d13] p-6 relative">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Dashboard / Resumo de Status (Exibido Sempre que um projeto estiver selecionado) -->
                <div id="dashboard-summary" class="hidden grid-cols-1 md:grid-cols-4 gap-4 fade-in mb-8">
                    <div class="glass-panel p-4 flex items-center gap-4">
                        <div class="p-3 bg-vs-blue/10 rounded-lg text-vs-blue">
                            <i data-lucide="files" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-vs-muted text-xs uppercase font-bold tracking-wider mb-0.5">Arquivos de Log</p>
                            <p class="text-2xl font-bold text-white" id="stat-files">0</p>
                        </div>
                    </div>
                    <div class="glass-panel p-4 flex items-center gap-4">
                        <div class="p-3 bg-vs-red/10 rounded-lg text-vs-red">
                            <i data-lucide="alert-circle" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-vs-muted text-xs uppercase font-bold tracking-wider mb-0.5">Arquivos c/ Erros Críticos</p>
                            <p class="text-2xl font-bold text-white" id="stat-errors">0</p>
                        </div>
                    </div>
                    <div class="glass-panel p-4 flex items-center gap-4">
                        <div class="p-3 bg-vs-yellow/10 rounded-lg text-vs-yellow">
                            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-vs-muted text-xs uppercase font-bold tracking-wider mb-0.5">Ocorrências de Warnings</p>
                            <p class="text-2xl font-bold text-white" id="stat-warns">0</p>
                        </div>
                    </div>
                    <div class="glass-panel p-4 flex items-center gap-4">
                        <div class="p-3 bg-vs-purple/10 rounded-lg text-vs-purple">
                            <i data-lucide="mail-warning" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-vs-muted text-xs uppercase font-bold tracking-wider mb-0.5">Logs de SMTP/Mail</p>
                            <p class="text-2xl font-bold text-white" id="stat-mails">0</p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar de Filtros -->
                <div class="glass-panel p-4 flex flex-col sm:flex-row gap-4 items-center justify-between fade-in hidden" id="filter-bar">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <i data-lucide="folder-search" class="w-5 h-5 text-vs-blue"></i>
                        <h2 class="text-base font-semibold text-white truncate" id="current-project-title">Selecione um projeto</h2>
                    </div>

                    <div class="relative w-full sm:max-w-md">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-vs-muted"></i>
                        <input type="text" id="search-input" placeholder="Buscar no nome do arquivo..." class="w-full pl-9 pr-4 py-1.5 bg-vs-bg border border-vs-border rounded-md text-sm text-vs-text focus:outline-none focus:border-vs-blue transition-colors placeholder-vs-muted">
                    </div>
                </div>

                <!-- Estado Inicial / Vazio -->
                <div id="empty-state" class="py-20 flex flex-col items-center justify-center fade-in">
                    <div class="relative mb-6">
                        <div class="absolute inset-0 bg-vs-blue/20 blur-2xl rounded-full"></div>
                        <div class="glass-panel p-6 relative">
                            <i data-lucide="activity" class="w-12 h-12 text-vs-blue"></i>
                        </div>
                    </div>
                    <h2 class="text-xl font-medium text-white mb-2">Monitoramento de Sistemas</h2>
                    <p class="text-vs-muted text-sm max-w-md text-center">Selecione um projeto no menu lateral para visualizar os arquivos de log, erros de servidor e tráfego de email.</p>
                </div>

                <!-- Grid de Cards de Logs -->
                <div id="logs-container" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5 hidden pb-8">
                    <!-- Cards entram aqui -->
                </div>

            </div>
        </main>
    </div>

    <!-- Modal: Detalhes do Log Estilo VSCode -->
    <div id="log-modal" class="fixed inset-0 z-50 p-2 sm:p-6 md:p-12 hidden transition-opacity opacity-0 duration-300">
        <!-- Fundo Escuro Base -->
        <div class="absolute inset-0 bg-[#010409] opacity-90 backdrop-blur-sm" onclick="closeModal()"></div>
        
        <div class="glass-panel relative w-full h-full flex flex-col shadow-2xl transform scale-95 opacity-0 transition-all duration-300" id="modal-content" style="background-color: theme('colors.vs.bg');">
            
            <!-- Modal Header -->
            <div class="flex-none bg-vs-panel border-b border-vs-border px-4 py-3 flex justify-between items-center rounded-t-xl">
                <div class="flex items-center gap-3 min-w-0">
                    <i data-lucide="file-code" class="w-5 h-5 text-vs-blue shrink-0"></i>
                    <h3 class="font-mono text-sm sm:text-base font-bold text-white truncate" id="modal-title">log.txt</h3>
                    <span id="modal-size" class="ml-2 text-xs font-sans px-2 py-0.5 bg-vs-border text-vs-muted rounded-md shrink-0">0 KB</span>
                </div>
                
                <div class="flex gap-2 shrink-0 ml-4">
                    <button class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-vs-text bg-white/5 hover:bg-white/10 border border-vs-border rounded-md transition-colors" onclick="copyLogContent()" id="copy-btn">
                        <i data-lucide="copy" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Copiar Conteúdo</span>
                    </button>
                    <button class="p-1.5 text-vs-muted hover:text-white hover:bg-vs-red rounded-md transition-colors" onclick="closeModal()">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Analyzed Log Output Container -->
            <div class="flex-1 overflow-x-auto overflow-y-auto bg-vs-bg py-4 selection:bg-vs-blue/30 relative" id="modal-body">
                <!-- Text Injected Here -->
            </div>
            
            <!-- VSCode Style Status Bar -->
            <div class="flex-none bg-[#007acc] px-3 py-1 flex justify-between items-center text-[11px] font-sans font-medium text-white rounded-b-xl shadow-inner shrink-0 z-10">
                <div class="flex items-center gap-4">
                    <span class="flex items-center gap-1"><i data-lucide="activity" class="w-3 h-3"></i> Analisador Ativo</span>
                    <span class="flex items-center gap-1" id="err-bar-wrap"><i data-lucide="x-circle" class="w-3 h-3 text-white"></i> <span id="err-count-display">0 Erros</span></span>
                    <span class="flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3 text-white"></i> <span id="warn-count-display">0 Avisos</span></span>
                </div>
                <div class="flex items-center gap-4 text-white/90">
                    <span id="modal-date" class="hidden sm:inline">Modificado: -</span>
                    <span>UTF-8</span>
                    <span>PHP Log</span>
                </div>
            </div>
        </div>
    </div>


    <!-- Engine JS e Lógicas -->
    <script>
        lucide.createIcons();

        const state = {
            currentProject: null,
            logs: [],
            projects: [
                { id: 'all', name: 'Visão Geral (Todos)' },
                { id: 'protocolosead_com', name: 'Protocolo SEAD' },
                { id: 'estagiopaudosferros_com', name: 'Estágio PDF' },
                { id: 'sema_paudosferros', name: 'SEMA PDF' },
                { id: 'demutran_protocolosead_com', name: 'Demutran SEAD' },
                { id: 'demutranpaudosferros', name: 'Demutran PDF' },
                { id: 'suap2_estagiopaudosferros_com', name: 'SUAP 2 BD' },
                { id: 'supaco_estagiopaudosferros_com', name: 'Supaco BD' },
                { id: 'api_estagiopaudosferros_com', name: 'API Estágio' },
                { id: 'api_protocolosead_com', name: 'API Protocolo' },
            ]
        };

        const DOM = {
            projectList: document.getElementById('project-list'),
            logsContainer: document.getElementById('logs-container'),
            emptyState: document.getElementById('empty-state'),
            dashboardSummary: document.getElementById('dashboard-summary'),
            filterBar: document.getElementById('filter-bar'),
            currentProjectTitle: document.getElementById('current-project-title'),
            searchInput: document.getElementById('search-input'),
            iconRefresh: document.getElementById('icon-refresh'),
            
            modal: document.getElementById('log-modal'),
            modalContent: document.getElementById('modal-content'),
            modalTitle: document.getElementById('modal-title'),
            modalBody: document.getElementById('modal-body'),
            modalSize: document.getElementById('modal-size'),
            modalDate: document.getElementById('modal-date'),
            errCount: document.getElementById('err-count-display'),
            warnCount: document.getElementById('warn-count-display'),
            errBarWrap: document.getElementById('err-bar-wrap'),

            // Stats
            statFiles: document.getElementById('stat-files'),
            statErrors: document.getElementById('stat-errors'),
            statWarns: document.getElementById('stat-warns'),
            statMails: document.getElementById('stat-mails'),
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

        const escapeHtml = (unsafe) => unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");

        function renderProjects() {
            DOM.projectList.innerHTML = state.projects.map(p => {
                const isActive = state.currentProject === p.id;
                let ic = p.id === 'all' ? 'layout-dashboard' : (p.id.includes('db') ? 'database' : 'folder-git-2');
                return `
                <button onclick="selectProject('${p.id}')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-sm transition-all rounded-md group
                        ${isActive 
                            ? 'bg-vs-blue/10 text-vs-blue font-semibold border border-vs-blue/20' 
                            : 'text-vs-muted hover:bg-white/5 hover:text-white border border-transparent'}">
                    <div class="flex items-center gap-2.5 truncate">
                        <i data-lucide="${ic}" class="w-4 h-4 shrink-0 ${isActive ? 'text-vs-blue' : 'opacity-70 group-hover:opacity-100'}"></i>
                        <span class="truncate mt-0.5">${p.name}</span>
                    </div>
                </button>
            `}).join('');
            lucide.createIcons();
        }

        function selectProject(projectId) {
            state.currentProject = projectId;
            const proj = state.projects.find(p => p.id === projectId);
            
            DOM.currentProjectTitle.textContent = proj ? proj.name : 'Múltiplos Projetos';
            
            // Trocar Views
            DOM.emptyState.classList.add('hidden');
            DOM.dashboardSummary.classList.remove('hidden');
            DOM.dashboardSummary.classList.add('grid');
            DOM.filterBar.classList.remove('hidden');
            DOM.logsContainer.classList.remove('hidden');
            
            renderProjects();
            DOM.searchInput.value = '';
            loadLogs();
        }

        async function loadLogs() {
            if (!state.currentProject) return;

            DOM.iconRefresh.classList.add('animate-spin');
            DOM.logsContainer.innerHTML = '<div class="col-span-full py-10 flex flex-col items-center justify-center text-vs-blue"><i data-lucide="loader-2" class="w-8 h-8 animate-spin"></i><p class="mt-2 text-sm font-medium">Lendo arquivos...</p></div>';
            lucide.createIcons();

            try {
                const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
                const data = await response.json();
                
                if (data.data) {
                    state.logs = data.data;
                    renderLogs();
                } else if (data.error) {
                    showError(data.error);
                }
            } catch (error) {
                console.error(error);
                showError("Falha de conexão ou leitura da pasta local de logs.");
            } finally {
                DOM.iconRefresh.classList.remove('animate-spin');
            }
        }

        function renderLogs() {
            let filtered = [...state.logs];
            const term = DOM.searchInput.value.toLowerCase().trim();
            if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

            // Dashboard Stats Logic
            let eCnt = 0, wCnt = 0, mailCnt = 0;

            if (filtered.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-vs-muted glass-panel border-dashed">
                        <i data-lucide="search-x" class="w-10 h-10 mb-3 opacity-50"></i>
                        <p class="font-medium">Nenhum registro encontrado para este projeto.</p>
                    </div>
                `;
            } else {
                DOM.logsContainer.innerHTML = filtered.map((log, index) => {
                    const previewLower = log.preview.toLowerCase();
                    const isErr = previewLower.includes('fatal error') || previewLower.includes('uncaught error') || previewLower.includes("doesn't exist");
                    const isWarn = previewLower.includes('warning') || previewLower.includes('notice');
                    const isMail = previewLower.includes('mail') || previewLower.includes('smtp');
                    
                    if (isErr) eCnt++;
                    // conta quantidade *aproximada* para warning ocorrencias contando matches
                    const warnMatches = (previewLower.match(/warning/g) || []).length;
                    wCnt += warnMatches;
                    if (isMail) mailCnt++;

                    // Card Header Badges
                    let badgesHtml = '';
                    if (isErr) badgesHtml += '<span class="px-1.5 py-0.5 rounded bg-vs-red/20 text-vs-red text-[10px] font-bold border border-vs-red/20 truncate">ERRO CRÍTICO</span>';
                    if (isWarn) badgesHtml += '<span class="px-1.5 py-0.5 rounded bg-vs-yellow/20 text-vs-yellow text-[10px] font-bold border border-vs-yellow/20 truncate">WARNINGS</span>';
                    if (isMail) badgesHtml += '<span class="px-1.5 py-0.5 rounded bg-vs-purple/20 text-vs-purple text-[10px] font-bold border border-vs-purple/20 truncate">SMTP</span>';
                    if (!badgesHtml) badgesHtml = '<span class="px-1.5 py-0.5 rounded bg-vs-green/10 text-vs-green/70 text-[10px] border border-vs-green/10">ROTINA</span>';

                    // Extract last couple readable lines for card preview
                    const lines = log.preview.split('\\n');
                    const lastLines = lines.slice(-2).join('\\n').trim();

                    return `
                    <div class="glass-panel card-hover flex flex-col group relative overflow-hidden transition-all duration-300 fade-in cursor-pointer" style="animation-delay: ${index * 0.05}s" onclick="openModal('${encodeURIComponent(JSON.stringify(log))}')">
                        
                        <div class="px-4 py-3 border-b border-vs-border bg-vs-panel flex justify-between items-start gap-2">
                            <div class="font-semibold text-white truncate w-full flex items-center gap-2" title="${log.file}">
                                <i data-lucide="file-json" class="w-4 h-4 text-vs-blue shrink-0"></i> 
                                <span class="truncate mt-0.5 text-sm">${log.file}</span>
                            </div>
                        </div>
                        
                        <!-- Badges ROW -->
                        <div class="px-4 py-2 border-b border-vs-border flex gap-1.5 flex-wrap items-center bg-[#090d13]">
                            ${badgesHtml}
                        </div>

                        <!-- Mini Code Preview -->
                        <div class="flex-1 p-4 text-[11px] font-mono break-all overflow-hidden text-vs-muted group-hover:text-vs-text relative bg-vs-bg">
                            ${escapeHtml(lastLines) || '<span class="italic opacity-50">Sem conteúdo para pré-visualizar...</span>'}
                            <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-vs-bg to-transparent pointer-events-none"></div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="px-4 py-2.5 border-t border-vs-border flex items-center justify-between text-[11px] text-vs-muted bg-vs-panel mt-auto">
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1.5"><i data-lucide="hard-drive" class="w-3.5 h-3.5"></i> ${formatBytes(log.size_bytes)}</span>
                                <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3 h-3"></i> ${formatDate(log.modified)}</span>
                            </div>
                        </div>
                    </div>
                `}).join('');
            }

            DOM.statFiles.textContent = filtered.length;
            DOM.statErrors.textContent = eCnt;
            DOM.statWarns.textContent = wCnt;
            DOM.statMails.textContent = mailCnt;

            lucide.createIcons();
        }

        function showError(msg) {
            DOM.logsContainer.innerHTML = `
                <div class="col-span-full py-16 flex flex-col items-center justify-center text-vs-red glass-panel">
                    <i data-lucide="server-crash" class="w-12 h-12 mb-3 bg-vs-red/10 p-2 rounded-full"></i>
                    <p class="font-bold text-lg mb-1">Falha de Leitura</p>
                    <p class="text-vs-red/70 text-sm max-w-md text-center">${msg}</p>
                </div>
            `;
            lucide.createIcons();
        }

        // --- SYNTAX HIGHLIGHTING COM REGEX (Estilo VSCode) ---
        function highlightLogLine(line) {
            let html = escapeHtml(line);
            
            // 1. Strings em Aspas (Verde)
            html = html.replace(/(&quot;.*?&quot;|&#039;.*?&#039;)/g, '<span class="hl-string">$1</span>');

            // 2. Variáveis Globais/Sintaxe do PHP (Ciano/Azul claro)
            html = html.replace(/(\$[A-Za-z0-9_]+)/g, '<span class="hl-var">$1</span>');

            // 3. Timestamps (Cinza/Muted) [05-Feb-2026 04:52:09 UTC]
            html = html.replace(/(\[[0-9]{2}-[a-zA-Z]{3}-[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} [A-Z]{3,4}\])/g, '<span class="hl-timestamp">$1</span>');

            // 4. Paths Unix (Azul / Linkável)
            html = html.replace(/(\/home[A-Za-z0-9_.\/-]+\/[A-Za-z0-9_.-]+\.[a-zA-Z0-9]+)\b/g, '<span class="hl-path" title="Caminho físico">$1</span>');

            // 5. Palavras chave (Amarelo / Roxo / Vermelho)
            const funcsAndKeywords = ['PHP Warning:', 'PHP Fatal error:', 'PHP Parse error:', 'Undefined array key', 'Undefined variable', 'syntax error', 'Stack trace:', 'thrown in', 'Failed opening'];
            for(let kw of funcsAndKeywords) {
                let regex = new RegExp(`(${kw})(?![^<]*>|[^<>]*<\/span>)`, 'gi');
                
                let cssClass = 'hl-keyword'; // roxo default
                if(kw.toLowerCase().includes('warning') || kw.toLowerCase().includes('undefined')) cssClass = 'hl-warning';
                if(kw.toLowerCase().includes('fatal') || kw.toLowerCase().includes('error') || kw.toLowerCase().includes('failed')) cssClass = 'hl-error';

                html = html.replace(regex, `<span class="${cssClass}">$1</span>`);
            }

            return html;
        }

        function parseLogContentFull(content) {
            if (!content) return { html: '<div class="text-vs-muted italic px-4 mt-4">Arquivo sem conteúdo disponível...</div>', e:0, w:0 };
            
            const lines = content.split('\\n');
            let parsedHtml = '';
            let errC = 0, warnC = 0;
            
            for (let i = 0; i < lines.length; i++) {
                let ln = lines[i];
                if (!ln.trim() && i === lines.length -1) continue; // ignora pular ultima linha vazia
                
                let lower = ln.toLowerCase();
                let rowWrapperClass = "log-line";

                // Severidade da Linha inteira (marca sutil no fundo)
                if (lower.includes('fatal error') || lower.includes('uncaught error') || lower.includes("doesn't exist")) {
                    rowWrapperClass += " row-critical";
                    errC++;
                } 
                else if (lower.includes('warning') || lower.includes('undefined array key') || lower.includes('notice')) {
                    rowWrapperClass += " row-warning";
                    warnC++;
                } 
                else if (lower.includes('parse error') || lower.includes('syntax error')) {
                    rowWrapperClass += " row-critical";
                    errC++;
                }

                if (lower.includes('mail') || lower.includes('smtp')) {
                    if (lower.includes('fail') || lower.includes('error')) {
                        rowWrapperClass += " row-critical";
                        errC++;
                    } else {
                        rowWrapperClass += " row-mail";
                    }
                }

                const pLine = highlightLogLine(ln);

                parsedHtml += \`
                <div class="\${rowWrapperClass}">
                    <div class="log-num">\${i+1}</div>
                    <div class="flex-1 whitespace-pre-wrap break-all">\${pLine || '&nbsp;'}</div>
                </div>\`;
            }
            
            return { html: parsedHtml, errC, warnC };
        }

        function openModal(logStrEnc) {
            try {
                const log = JSON.parse(decodeURIComponent(logStrEnc));
                DOM.modalTitle.textContent = log.file;
                DOM.modalSize.textContent = formatBytes(log.size_bytes);
                DOM.modalDate.textContent = \`Modificado: \${formatDate(log.modified)}\`;
                
                // Processa o Highlighting com Cores Novas e Fundo Escuro
                const parseRs = parseLogContentFull(log.preview);
                DOM.modalBody.innerHTML = parseRs.html;
                
                // Status Inferior
                DOM.errCount.innerText = \`\${parseRs.errC} Erros Críticos\`;
                if(parseRs.errC > 0) {
                    DOM.errBarWrap.classList.add('bg-vs-red/80', 'px-2', 'py-0.5', 'rounded', 'shadow-sm');
                } else {
                    DOM.errBarWrap.classList.remove('bg-vs-red/80', 'px-2', 'py-0.5', 'rounded', 'shadow-sm');
                }
                DOM.warnCount.innerText = \`\${parseRs.warnC} Avisos\`;

                DOM.modal.classList.remove('hidden');
                void DOM.modal.offsetWidth; // reflow
                DOM.modal.classList.remove('opacity-0');
                DOM.modalContent.classList.remove('opacity-0', 'scale-95');
                DOM.modalContent.classList.add('scale-100');

                lucide.createIcons();
            } catch(e) { console.error('Erro ao abrir Modal Logs:', e); }
        }

        function closeModal() {
            DOM.modal.classList.add('opacity-0');
            DOM.modalContent.classList.remove('scale-100');
            DOM.modalContent.classList.add('opacity-0', 'scale-95');
            
            setTimeout(() => {
                DOM.modal.classList.add('hidden');
                DOM.modalBody.innerHTML = '';
            }, 300);
        }

        function copyLogContent() {
            let text = DOM.modalBody.innerText.replace(/^[0-9]+[ \\t]*/gm, ''); // remove number lines for copying
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copy-btn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5 text-vs-green"></i> <span class="hidden sm:inline text-vs-green">Copiado!</span>';
                lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }, 2000);
            }).catch(e => console.error('Falha de ctrl+c', e));
        }

        DOM.searchInput.addEventListener('input', () => setTimeout(renderLogs, 300));
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !DOM.modal.classList.contains('hidden')) closeModal();
        });

        // Initialize empty state view navigation
        renderProjects();
    </script>
</body>
</html>
