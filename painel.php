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
    <title>SYS.LOG.TERMINAL // SECURE_ACCESS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --term-bg: #030806;
            --term-green: #00ff41;
            --term-cyan: #00f0ff;
            --term-red: #ff003c;
            --term-yellow: #fcee0a;
            --term-purple: #b100ff;
            --term-dim: rgba(0, 255, 65, 0.3);
            --term-glow: rgba(0, 255, 65, 0.15);
        }
        
        body { 
            font-family: 'JetBrains Mono', monospace; 
            background-color: var(--term-bg);
            color: var(--term-green);
            overflow: hidden;
            /* Subtile scanline effect */
            background-image: linear-gradient(rgba(0, 255, 65, 0.02) 50%, transparent 50%);
            background-size: 100% 4px;
            pointer-events: auto;
        }

        /* Screen distortion/vignette effect */
        body::before {
            content: " ";
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: radial-gradient(circle, rgba(0,0,0,0) 60%, rgba(0,0,0,0.6) 100%);
            pointer-events: none;
            z-index: 100;
        }
        
        .edex-border {
            border: 1px solid var(--term-dim);
            box-shadow: inset 0 0 15px var(--term-glow);
            background: rgba(0, 15, 5, 0.6);
            backdrop-filter: blur(4px);
            position: relative;
        }
        
        /* Corner accents for borders */
        .edex-border::after {
            content: '';
            position: absolute;
            top: -1px; left: -1px; right: -1px; bottom: -1px;
            border: 1px solid transparent;
            /* Create corner markers using background linear gradients */
            background: 
                linear-gradient(var(--term-cyan), var(--term-cyan)) top left,
                linear-gradient(var(--term-cyan), var(--term-cyan)) top left,
                linear-gradient(var(--term-cyan), var(--term-cyan)) bottom right,
                linear-gradient(var(--term-cyan), var(--term-cyan)) bottom right;
            background-size: 8px 1px, 1px 8px;
            background-repeat: no-repeat;
            pointer-events: none;
            z-index: 10;
        }

        .edex-header {
            border-bottom: 2px solid var(--term-dim);
            background: rgba(0, 240, 255, 0.05);
            color: var(--term-cyan);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 2px 10px rgba(0, 240, 255, 0.1);
        }

        .scroll-hacker::-webkit-scrollbar { width: 8px; height: 8px; }
        .scroll-hacker::-webkit-scrollbar-track { background: rgba(0, 20, 0, 0.5); border-left: 1px solid var(--term-dim); }
        .scroll-hacker::-webkit-scrollbar-thumb { background: var(--term-dim); border: 1px solid var(--term-cyan); }
        .scroll-hacker::-webkit-scrollbar-thumb:hover { background: var(--term-cyan); }
        .scroll-hacker::-webkit-scrollbar-corner { background: transparent; }

        .animate-blink { animation: blink 1.2s step-end infinite; }
        @keyframes blink { 50% { opacity: 0; } }

        .line-glow { text-shadow: 0 0 5px currentColor; }

        .btn-hacker {
            border: 1px solid var(--term-dim);
            color: var(--term-green);
            background: rgba(0, 255, 65, 0.05);
            transition: all 0.2s;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }
        .btn-hacker:hover {
            background: rgba(0, 255, 65, 0.2);
            border-color: var(--term-green);
            color: #fff;
            text-shadow: 0 0 5px var(--term-green);
            box-shadow: 0 0 10px var(--term-glow);
        }
        .btn-hacker:active {
            transform: scale(0.98);
        }

        /* Glitch effect on hover for warnings */
        .glitch-hover:hover {
            animation: glitch 0.2s cubic-bezier(.25, .46, .45, .94) both infinite;
            color: var(--term-red);
        }
        @keyframes glitch {
            0% { transform: translate(0) }
            20% { transform: translate(-2px, 2px) }
            40% { transform: translate(-2px, -2px) }
            60% { transform: translate(2px, 2px) }
            80% { transform: translate(2px, -2px) }
            100% { transform: translate(0) }
        }

        /* Error/Warning Blocks */
        .fatal-block { border-left-color: var(--term-red) !important; color: #ff6b81; background: rgba(255, 0, 60, 0.1) !important; }
        .warn-block { border-left-color: var(--term-yellow) !important; color: #ffd32a; background: rgba(252, 238, 10, 0.05) !important; }
        .mail-block { border-left-color: var(--term-purple) !important; color: #d980fa; background: rgba(177, 0, 255, 0.05) !important; }
        .mail-fail-block { border-left-color: #ff3f34 !important; color: #ff3f34; font-weight: bold; background: rgba(255, 63, 52, 0.2) !important; }
        .info-block { border-left-color: var(--term-cyan) !important; color: #4bcffa; background: rgba(0, 240, 255, 0.03) !important; }

    </style>
</head>
<body class="flex h-screen flex-col p-2 sm:p-4 gap-3 text-xs sm:text-sm">
    
    <!-- Top Nav/Header -->
    <header class="edex-border flex flex-col sm:flex-row items-center justify-between px-4 py-3 shrink-0 gap-3 z-10">
        <div class="flex items-center gap-4 w-full sm:w-auto justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="cpu" class="w-6 h-6 text-cyan-400"></i>
                <span class="text-cyan-400 font-bold tracking-widest text-lg sm:text-xl line-glow">
                    SYS.LOG.TERMINAL<span class="animate-blink">_</span>
                </span>
            </div>
            <span class="hidden md:inline px-3 py-1 bg-green-900/40 text-green-400 border border-green-500/50 rounded-sm font-bold text-[10px] tracking-wider">
                SECURE_CONNECTION: ESTABLISHED // PORT 22
            </span>
        </div>

        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
            <div id="loading-indicator" class="text-yellow-400 font-bold items-center gap-2 hidden">
                <span class="animate-pulse">[FETCHING_DATA...]</span>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="text-cyan-400 border border-cyan-500/50 px-3 py-1 bg-cyan-900/20 shadow-[0_0_8px_rgba(0,240,255,0.2)]">
                    USR: <?= strtoupper($_SESSION['username']) ?>_ADM
                </span>
                <a href="logout.php" class="btn-hacker !border-red-500/50 !text-red-500 hover:!bg-red-500/20 px-3 py-1 flex items-center gap-1">
                    <i data-lucide="power" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">[LOGOUT]</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col lg:flex-row gap-3 overflow-hidden z-10">
        
        <!-- Sidebar: Project Structure -->
        <aside class="edex-border w-full lg:w-72 flex flex-col shrink-0 h-48 lg:h-auto">
            <div class="edex-header p-3 font-bold flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="hard-drive" class="w-4 h-4 text-cyan-400"></i> SYSTEM_NODES
                </div>
                <div class="text-[10px] text-cyan-400/50">MNT/DEV/LOGS</div>
            </div>
            
            <div class="flex-1 overflow-y-auto scroll-hacker p-2 space-y-[2px]" id="project-list">
                <!-- Rendered dynamically -->
            </div>
            
            <!-- Realtime Metrics Mock -->
            <div class="border-t border-green-500/30 bg-black/40 p-3 text-[10px] sm:text-xs">
                <div class="text-cyan-400 mb-2 border-b border-cyan-500/30 pb-1 w-full flex justify-between">
                    <span>HARDWARE_METRICS</span>
                    <i data-lucide="activity" class="w-3 h-3 animate-pulse"></i>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                    <div class="flex justify-between"><span>CPU_USAGE:</span> <span class="text-green-400 text-right">04.2%</span></div>
                    <div class="flex justify-between"><span>MEM_ALLOC:</span> <span class="text-yellow-400 text-right">68.1%</span></div>
                    <div class="flex justify-between"><span>I/O_DISK:</span> <span class="text-green-400 text-right">OK</span></div>
                    <div class="flex justify-between"><span>NET_PKG:</span> <span id="net-status" class="text-cyan-400 animate-pulse text-right">TX/RX</span></div>
                    <div class="flex justify-between col-span-2 pt-1 mt-1 border-t border-green-500/20">
                        <span>SYS_UPTIME:</span> <span class="text-green-400"><span id="uptime">0d 0h 0m</span></span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Center: Datagrid / Log Files -->
        <main class="edex-border flex-1 flex flex-col min-w-0">
            <!-- Header/Controls -->
            <div class="edex-header p-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-black/60">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <i data-lucide="terminal-square" class="w-5 h-5 text-cyan-400"></i>
                    <span id="current-project-title" class="font-bold tracking-wide break-all">TARGET: NONE</span>
                </div>
                
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                    <div class="relative w-full sm:w-auto group">
                        <i data-lucide="search" class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-green-500/50 group-focus-within:text-cyan-400"></i>
                        <input type="text" id="search-input" placeholder="> GREP_FILTER_FILE" class="bg-black border border-green-500/50 text-green-400 pl-8 pr-2 py-1.5 outline-none focus:border-cyan-400 focus:shadow-[0_0_8px_rgba(0,240,255,0.4)] placeholder-green-700 w-full sm:w-48 lg:w-64 transition-all">
                    </div>
                    
                    <button onclick="loadLogs()" class="btn-hacker px-3 py-1.5 flex-1 sm:flex-none flex justify-center items-center gap-2">
                        <i data-lucide="refresh-cw" id="icon-refresh" class="w-4 h-4"></i>
                        <span>[EXEC_SYNC]</span>
                    </button>
                </div>
            </div>

            <!-- Previews Grid -->
            <div class="flex-1 overflow-y-auto scroll-hacker p-4 grid grid-cols-1 2xl:grid-cols-2 gap-4 content-start" id="logs-container">
                <div class="col-span-full h-full min-h-[50vh] flex flex-col items-center justify-center text-green-500/30 space-y-4">
                    <i data-lucide="radar" class="w-16 h-16 animate-spin-slow opacity-50"></i>
                    <p class="font-bold tracking-widest text-lg animate-pulse">> AWAITING_TARGET_SELECTION...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Details View Terminal (Overlay) -->
    <div id="log-modal" class="fixed inset-0 z-50 p-0 sm:p-4 md:p-8 hidden bg-black/90 backdrop-blur-md transition-opacity opacity-0 duration-300">
        <div class="edex-border w-full h-full flex flex-col bg-[#020503] shadow-[0_0_40px_rgba(0,240,255,0.15)] transform scale-95 opacity-0 transition-all duration-300" id="modal-content">
            
            <!-- Modal Header -->
            <div class="flex-none edex-header p-3 sm:p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-black/80">
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-3">
                        <i data-lucide="file-code" class="w-5 h-5 text-cyan-400 shrink-0"></i>
                        <span id="modal-title" class="font-bold text-base sm:text-xl text-white tracking-widest truncate">/var/log/file.log</span>
                    </div>
                    <span id="modal-meta" class="text-xs text-cyan-500/70 mt-1 flex items-center gap-2">
                        <i data-lucide="info" class="w-3 h-3"></i> <span>SIZE: 0B | TS: N/A</span>
                    </span>
                </div>
                
                <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                    <button class="btn-hacker px-4 py-2 flex-1 sm:flex-none flex justify-center items-center gap-2 !border-cyan-500/50 !text-cyan-400 hover:!bg-cyan-500/20" onclick="copyLogContent()" id="copy-btn">
                        <i data-lucide="copy" class="w-4 h-4"></i> <span>[YANK]</span>
                    </button>
                    <button class="btn-hacker px-4 py-2 flex-1 sm:flex-none flex justify-center items-center gap-2 !border-red-500/50 !text-red-500 hover:!bg-red-500/20" onclick="closeModal()">
                        <i data-lucide="x-square" class="w-4 h-4"></i> <span>[KILL_PROC]</span>
                    </button>
                </div>
            </div>
            
            <!-- Analyzed Log Output -->
            <div class="flex-1 overflow-x-auto overflow-y-auto scroll-hacker p-0 m-2 border border-green-500/20 bg-black/50" id="modal-body">
                <!-- Data injected dynamically -->
            </div>
            
            <!-- Terminal Status Bar -->
            <div class="flex-none border-t border-cyan-500/30 p-2 text-[10px] sm:text-xs text-cyan-500 bg-cyan-900/10 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="animate-pulse">_</span>
                    <span>PROCESS: LOG_ANALYZER V2.0 // EOF_REACHED</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline" id="err-count-display">ERRORS_DETECTED: 0</span>
                    <span class="hidden sm:inline" id="warn-count-display">WARNINGS: 0</span>
                    <span>ENCODING: UTF-8</span>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Logic -->
    <script>
        lucide.createIcons();

        // Uptime counter mockup
        let uptimeS = Math.floor(Math.random()*10000);
        setInterval(() => {
            uptimeS++;
            const d = Math.floor(uptimeS / 86400);
            const h = Math.floor((uptimeS % 86400) / 3600);
            const m = Math.floor((uptimeS % 3600) / 60);
            document.getElementById('uptime').innerText = `${d}d ${h}h ${m}m`;
        }, 60000); //update every min
        document.getElementById('uptime').innerText = `${Math.floor(uptimeS / 86400)}d ${Math.floor((uptimeS % 86400) / 3600)}h ${Math.floor((uptimeS % 3600) / 60)}m`;

        const state = {
            currentProject: null,
            logs: [],
            projects: [
                { id: 'all', name: 'ROOT_/[ALL_SYS_LOGS]' },
                { id: 'protocolosead_com', name: 'SYS_PROTOCOL_SEAD' },
                { id: 'estagiopaudosferros_com', name: 'SYS_ESTAGIO_PDF' },
                { id: 'sema_paudosferros', name: 'SYS_SEMA_PDF' },
                { id: 'demutran_protocolosead_com', name: 'SYS_DEMUTRAN_SEAD' },
                { id: 'demutranpaudosferros', name: 'SYS_DEMUTRAN_PDF' },
                { id: 'suap2_estagiopaudosferros_com', name: 'DB_SUAP_2' },
                { id: 'supaco_estagiopaudosferros_com', name: 'DB_SUPACO' },
                { id: 'api_estagiopaudosferros_com', name: 'API_ESTAGIO' },
                { id: 'api_protocolosead_com', name: 'API_PROTOCOLO' },
            ]
        };

        const DOM = {
            projectList: document.getElementById('project-list'),
            logsContainer: document.getElementById('logs-container'),
            currentProjectTitle: document.getElementById('current-project-title'),
            searchInput: document.getElementById('search-input'),
            iconRefresh: document.getElementById('icon-refresh'),
            loadingInd: document.getElementById('loading-indicator'),
            modal: document.getElementById('log-modal'),
            modalContent: document.getElementById('modal-content'),
            modalTitle: document.getElementById('modal-title'),
            modalBody: document.getElementById('modal-body'),
            modalMeta: document.getElementById('modal-meta'),
            errCount: document.getElementById('err-count-display'),
            warnCount: document.getElementById('warn-count-display'),
        };

        const formatBytes = (bytes) => {
            if (bytes === 0) return '0B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + sizes[i];
        };

        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return date.toISOString().replace('T', ' ').substring(0, 19);
        };

        const escapeHtml = (unsafe) => unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");

        function renderProjects() {
            DOM.projectList.innerHTML = state.projects.map(p => {
                const isActive = state.currentProject === p.id;
                return `
                <button onclick="selectProject('${p.id}')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-left transition-all border-l-2 group
                        ${isActive 
                            ? 'bg-cyan-900/40 border-cyan-400 text-cyan-300 font-bold shadow-[inset_4px_0_0_rgba(0,240,255,0.4)]' 
                            : 'border-transparent text-green-600 hover:bg-green-900/20 hover:text-green-400 hover:border-green-500/50'}">
                    <div class="flex items-center gap-2 truncate">
                        <i data-lucide="${p.id==='all' ? 'layers' : (p.id.includes('db') ? 'database' : 'folder-git-2')}" class="w-4 h-4 shrink-0 opacity-70 group-hover:opacity-100"></i>
                        <span class="truncate mt-0.5">./${p.name}</span>
                    </div>
                </button>
            `}).join('');
            lucide.createIcons();
        }

        function selectProject(projectId) {
            state.currentProject = projectId;
            const proj = state.projects.find(p => p.id === projectId);
            DOM.currentProjectTitle.textContent = `TARGET: ${proj ? proj.name : 'UNKNOWN'} // [MOUNTED]`;
            renderProjects();
            loadLogs();
        }

        async function loadLogs() {
            if (!state.currentProject) return;

            DOM.iconRefresh.classList.add('animate-spin');
            DOM.loadingInd.classList.remove('hidden');
            DOM.loadingInd.classList.add('flex');

            try {
                const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
                const data = await response.json();
                
                if (data.data) {
                    state.logs = data.data;
                    renderLogs();
                } else if (data.error) {
                    showError(data.error);
                } else {
                    showError("ERR_NULL_RESPONSE");
                }
            } catch (error) {
                console.error(error);
                showError("ERR_CONNECTION_REFUSED_OR_DIR_NOT_FOUND");
            } finally {
                DOM.iconRefresh.classList.remove('animate-spin');
                DOM.loadingInd.classList.add('hidden');
                DOM.loadingInd.classList.remove('flex');
            }
        }

        // Mini syntax highlighter for the preview cards
        function getHighlightsFromPreview(content) {
            let lower = content.toLowerCase();
            let hintsHtml = '';
            
            // Critical
            if (lower.includes('fatal error') || lower.includes('exception') || lower.includes("doesn't exist")) {
                hintsHtml += '<span class="px-1.5 py-0.5 bg-red-900/50 text-red-500 border border-red-500/30 text-[10px] uppercase font-bold glitch-hover">[FATAL_ERR]</span> ';
            }
            // Warning/Notice/Syntax
            if (lower.includes('warning') || lower.includes('syntax error') || lower.includes('undefined array key')) {
                hintsHtml += '<span class="px-1.5 py-0.5 bg-yellow-900/30 text-yellow-400 border border-yellow-500/30 text-[10px] uppercase font-bold">[WARN/SYNTAX]</span> ';
            }
            // Mail/Upload Critical
            if (lower.includes('mail') || lower.includes('smtp')) {
                let badgeClass = lower.includes('fail') || lower.includes('error') ? 'bg-red-900/50 text-red-400 border-red-400/50 blink' : 'bg-purple-900/30 text-purple-400 border-purple-500/30';
                hintsHtml += `<span class="px-1.5 py-0.5 ${badgeClass} border text-[10px] uppercase font-bold">[SMTP_TRACE]</span> `;
            }
            if (lower.includes('file not found')) {
                hintsHtml += '<span class="px-1.5 py-0.5 bg-cyan-900/30 text-cyan-300 border border-cyan-500/30 text-[10px] uppercase font-bold">[MISSING_FILE]</span> ';
            }

            if(!hintsHtml) {
                hintsHtml = '<span class="px-1.5 py-0.5 bg-green-900/20 text-green-500/50 border border-green-500/20 text-[10px] uppercase">[ROUTINE_LOG]</span>';
            }

            return hintsHtml;
        }

        function renderLogs() {
            if (!state.logs || state.logs.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="col-span-full h-full min-h-[40vh] flex flex-col items-center justify-center text-green-500/50">
                        <i data-lucide="package-open" class="w-12 h-12 mb-4 opacity-50"></i>
                        <p class="font-bold tracking-widest">[DIR_EMPTY] ZERO_LOGS_FOUND_FOR_TARGET</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            let filtered = [...state.logs];
            const term = DOM.searchInput.value.toLowerCase().trim();
            if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

            if (filtered.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="col-span-full h-full min-h-[40vh] flex flex-col items-center justify-center text-yellow-500/50">
                        <i data-lucide="filter-X" class="w-12 h-12 mb-4 opacity-50"></i>
                        <p class="font-bold tracking-widest">[GREP_FAIL] NO_MATCH_FOUND</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            DOM.logsContainer.innerHTML = filtered.map((log) => {
                const statusHints = getHighlightsFromPreview(log.preview);
                // Extract last couple lines for preview
                const lines = log.preview.split('\\n');
                const lastLines = lines.slice(-3).join('\\n').trim();

                return `
                <div class="bg-black/60 border border-green-500/30 flex flex-col group relative overflow-hidden h-48 sm:h-56 transform transition-all hover:-translate-y-1 hover:border-cyan-500/60 hover:shadow-[0_4px_20px_rgba(0,240,255,0.1)]">
                    
                    <!-- decorative corner -->
                    <div class="absolute top-0 right-0 w-8 h-8 opacity-20 pointer-events-none" style="background: linear-gradient(-45deg, var(--term-cyan) 50%, transparent 50%);"></div>

                    <div class="p-3 border-b border-green-500/20 bg-green-900/10 flex justify-between items-start gap-2">
                        <div class="font-bold text-cyan-300 truncate w-full flex items-center gap-2" title="${log.file}">
                            <i data-lucide="file-json-2" class="w-4 h-4 shrink-0 opacity-70"></i> 
                            <span class="truncate mt-0.5">${log.file}</span>
                        </div>
                    </div>
                    
                    <!-- Insights badges -->
                    <div class="px-3 py-1.5 border-b border-green-500/10 flex gap-1 flex-wrap items-center bg-black">
                        ${statusHints}
                    </div>

                    <div class="flex-1 p-3 text-[10px] sm:text-xs font-mono break-all overflow-hidden text-green-500/60 group-hover:text-green-400 relative">
                        <span class="opacity-50 select-none">... </span>
                        ${escapeHtml(lastLines) || '[NO_READABLE_TEXT]'}
                        <!-- Fade bottom -->
                        <div class="absolute bottom-0 left-0 right-0 h-6 bg-gradient-to-t from-black to-transparent pointer-events-none"></div>
                    </div>
                    
                    <div class="p-2 border-t border-green-500/20 bg-black flex items-center justify-between text-[10px] text-green-500/50 mt-auto">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center gap-1"><i data-lucide="hard-drive" class="w-3 h-3"></i> ${formatBytes(log.size_bytes)}</span>
                            <span class="flex items-center gap-1 hidden sm:flex"><i data-lucide="clock" class="w-3 h-3"></i> ${formatDate(log.modified)}</span>
                        </div>
                        <button onclick="openModal('${encodeURIComponent(JSON.stringify(log))}')" class="btn-hacker px-3 py-1 !text-cyan-400 !border-cyan-500/30">
                            [VIEW_DUMP]
                        </button>
                    </div>
                </div>
            `}).join('');

            lucide.createIcons();
        }

        function showError(msg) {
            DOM.logsContainer.innerHTML = `
                <div class="col-span-full h-full min-h-[40vh] flex flex-col items-center justify-center text-red-500">
                    <div class="bg-red-900/20 p-4 border border-red-500/50 mb-4 animate-pulse">
                        <i data-lucide="alert-triangle" class="w-12 h-12 text-red-500"></i>
                    </div>
                    <p class="font-bold text-red-500 text-lg mb-1 tracking-widest">[SYSTEM_FAILURE]</p>
                    <p class="text-red-400/70 text-sm max-w-md text-center font-mono">${msg}</p>
                </div>
            `;
            lucide.createIcons();
        }

        // Advanced parser to highlight lines for the Detailed view
        function parseLogContentFull(content) {
            if (!content) return '> NO_DATA_STREAM';
            
            const lines = content.split('\\n');
            let parsedHtml = '';
            let errC = 0, warnC = 0;
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i];
                if (!line.trim()) continue;
                
                let lower = line.toLowerCase();
                let className = 'text-green-400/80 mb-1 border-l border-green-500/20 pl-2';
                let iconHtml = '';
                let extraPrefix = '';

                // Classification Rules
                if (lower.includes('fatal error') || lower.includes('uncaught error') || lower.includes('exception')) {
                    className = 'fatal-block mb-2 py-1 pl-2 border-l-4 font-bold';
                    iconHtml = '<span class="mr-2 opacity-80">[FATAL_ERR]</span>';
                    errC++;
                } else if (lower.includes("doesn't exist") && lower.includes("table")) {
                    className = 'fatal-block mb-2 py-1 pl-2 border-l-4 font-black';
                    iconHtml = '<span class="mr-2 animate-pulse">[DB_FATAL]</span>';
                    errC++;
                } else if (lower.includes('warning') || lower.includes('undefined array key') || lower.includes('notice')) {
                    className = 'warn-block mb-1 py-1 pl-2 border-l-2';
                    iconHtml = '<span class="mr-2 opacity-80">[WARN/NOTICE]</span>';
                    warnC++;
                } else if (lower.includes('parse error') || lower.includes('syntax error')) {
                    className = 'warn-block mb-2 py-1 pl-2 border-l-4 !border-orange-500 !text-orange-400 font-bold';
                    iconHtml = '<span class="mr-2 opacity-80">[SYNTAX_ERR]</span>';
                    errC++;
                }
                
                // Specific Logic Highlights
                if (lower.includes('mail') || lower.includes('smtp')) {
                    if (lower.includes('fail') || lower.includes('error')) {
                        className = 'mail-fail-block mb-2 py-1 pl-2 border-l-4';
                        iconHtml = '<span class="mr-2">[SMTP_CRITICAL]</span>';
                        errC++;
                    } else {
                        className = 'mail-block mb-1 pl-2 border-l-2';
                        iconHtml = '<span class="mr-2">[SMTP_TRACE]</span>';
                    }
                }
                if (lower.includes('file not found') || lower.includes('no such file')) {
                    className = 'info-block mb-1 py-1 pl-2 border-l-4 font-bold text-cyan-300';
                    iconHtml = '<span class="mr-2">[MISSING_FILE]</span>';
                    errC++;
                }

                parsedHtml += `<div class="${className} font-mono leading-relaxed break-all hover:bg-white/5 transition-colors group">
                    <span class="text-green-700/50 mr-2 inline-block w-8 text-right select-none group-hover:text-green-500">${i+1}</span>
                    ${iconHtml}${escapeHtml(line)}
                </div>`;
            }
            
            return { html: parsedHtml, errC, warnC };
        }

        function openModal(logStrEnc) {
            try {
                const log = JSON.parse(decodeURIComponent(logStrEnc));
                DOM.modalTitle.textContent = log.file;
                DOM.modalMeta.innerHTML = `<i data-lucide="hard-drive" class="w-3 h-3"></i> <span>SIZE: ${formatBytes(log.size_bytes)} | TS: ${formatDate(log.modified)}</span>`;
                
                const parseRs = parseLogContentFull(log.preview);
                
                DOM.modalBody.innerHTML = parseRs.html || 'ERR_NO_DATA';
                DOM.errCount.innerText = `ERRORS_DETECTED: ${parseRs.errC}`;
                if(parseRs.errC > 0) DOM.errCount.classList.add('text-red-500', 'font-bold', 'animate-pulse');
                else DOM.errCount.classList.remove('text-red-500', 'font-bold', 'animate-pulse');

                DOM.warnCount.innerText = `WARNINGS: ${parseRs.warnC}`;

                DOM.modal.classList.remove('hidden');
                
                // Trigger reflow to animate in
                void DOM.modal.offsetWidth; 
                
                DOM.modal.classList.remove('opacity-0');
                DOM.modalContent.classList.remove('opacity-0', 'scale-95');
                DOM.modalContent.classList.add('scale-100');

                lucide.createIcons();
            } catch(e) { console.error(e); }
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
            // Need plain text without html tags
            let text = DOM.modalBody.innerText.replace(/^[ \\t]*\\d+[ \\t]+/gm, ''); // crude remove line numbers
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('copy-btn');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-green-400"></i><span>[YANK_SUCCESS]</span>';
                btn.classList.add('!border-green-400', '!text-green-400');
                lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('!border-green-400', '!text-green-400');
                    lucide.createIcons();
                }, 2000);
            }).catch(e => console.error('Failed to copy', e));
        }

        DOM.searchInput.addEventListener('input', () => setTimeout(renderLogs, 300));
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !DOM.modal.classList.contains('hidden')) closeModal();
        });

        // Init
        renderProjects();
        // default select first if wanted, or let it 'AWAITING_TARGET_SELECTION'
    </script>
</body>
</html>
