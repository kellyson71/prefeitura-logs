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
    <title>Painel de Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        .log-scroll::-webkit-scrollbar { width: 8px; }
        .log-scroll::-webkit-scrollbar-track { background: #1f2937; border-radius: 4px; }
        .log-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        .log-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }

        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 flex h-screen overflow-hidden">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden lg:hidden transition-opacity" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white w-72 flex-shrink-0 border-r border-gray-200 flex flex-col fixed inset-y-0 left-0 transform -translate-x-full lg:relative lg:translate-x-0 transition-transform duration-300 ease-in-out z-50">
        <div class="h-16 flex items-center px-6 border-b border-gray-200">
            <div class="bg-blue-600 p-2 rounded-lg mr-3 shadow-sm">
                <i data-lucide="terminal" class="w-5 h-5 text-white"></i>
            </div>
            <span class="text-lg font-bold text-gray-900 mx-auto">Painel de Logs</span>
            <button class="lg:hidden ml-auto p-2 text-gray-500 hover:bg-gray-100 rounded-md" onclick="toggleSidebar()">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="px-5 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
            Projetos
        </div>

        <nav class="flex-1 overflow-y-auto px-3 space-y-1 pb-4" id="project-list">
            <!-- Renderizado via JS -->
        </nav>

        <div class="p-4 border-t border-gray-200 bg-gray-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-700 font-bold shadow-sm">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p class="text-xs text-gray-500 truncate">Administrador</p>
                </div>
                <a href="logout.php" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Sair">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shrink-0">
            <div class="flex items-center gap-4">
                <button class="lg:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-md -ml-2 transition-colors" onclick="toggleSidebar()">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 bg-gray-100 rounded-md">
                        <i data-lucide="folder-open" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800" id="current-project-title">Selecione um projeto</h2>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <button onclick="loadLogs()" class="flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 active:scale-95">
                    <i data-lucide="refresh-cw" class="w-4 h-4" id="icon-refresh"></i>
                    <span class="hidden sm:inline">Atualizar logs</span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-gray-50/50 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="relative w-full sm:max-w-md">
                        <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                        <input type="text" id="search-input" placeholder="Buscar no nome do log..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all placeholder-gray-400">
                    </div>
                    
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-auto">
                            <i data-lucide="arrow-down-up" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <select id="sort-select" class="w-full sm:w-auto bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:bg-white pl-9 pr-8 py-2 appearance-none outline-none transition-all cursor-pointer">
                                <option value="desc">Mais recentes</option>
                                <option value="asc">Mais antigos</option>
                            </select>
                        </div>
                        <div class="relative w-full sm:w-auto">
                            <i data-lucide="list-filter" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <select id="limit-select" class="w-full sm:w-auto bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:bg-white pl-9 pr-8 py-2 appearance-none outline-none transition-all cursor-pointer">
                                <option value="all">Todos</option>
                                <option value="5">Últimos 5</option>
                                <option value="10">Últimos 10</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Logs Grid -->
                <div id="logs-container" class="grid grid-cols-1 md:grid-cols-2 xlg:grid-cols-3 gap-6">
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-gray-400">
                        <div class="bg-gray-100 p-4 rounded-full mb-4">
                            <i data-lucide="layout-dashboard" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <p class="text-lg font-medium text-gray-600 mb-1">Selecione um projeto</p>
                        <p class="text-sm">Escolha um projeto na barra lateral para visualizar seus logs.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-4 px-6 shrink-0 z-10 text-center sm:text-right">
            <p class="text-sm text-gray-500 flex items-center justify-center sm:justify-end gap-1.5 font-medium">
                Desenvolvido por Kellyson <span class="text-lg">💻</span>
            </p>
        </footer>
    </div>

    <!-- Modal View Log -->
    <div id="log-modal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="modal-backdrop" onclick="closeModal()"></div>
        <div class="flex items-center justify-center min-h-screen p-4 sm:p-6 lg:p-8">
            <div class="bg-gray-900 text-gray-100 rounded-2xl w-full max-w-5xl shadow-2xl relative flex flex-col max-h-[90vh] md:max-h-[85vh] transform scale-95 opacity-0 transition-all duration-300 border border-gray-700" id="modal-content">
                
                <div class="flex items-center justify-between p-4 sm:p-5 border-b border-gray-800 shrink-0 bg-gray-900 rounded-t-2xl z-10">
                    <div class="flex items-center gap-3 mx-2 overflow-hidden">
                        <div class="bg-gray-800 p-2 rounded-lg shrink-0 border border-gray-700">
                            <i data-lucide="file-code-2" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <h3 class="font-semibold text-lg truncate text-gray-100 tracking-wide" id="modal-title">log.txt</h3>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-4">
                        <button class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800 rounded-lg transition-colors border border-gray-700" onclick="copyLogContent()">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Copiar</span>
                        </button>
                        <button class="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-800 rounded-lg transition-colors" onclick="closeModal()">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-5 overflow-y-auto log-scroll flex-1 font-mono text-sm leading-relaxed whitespace-pre-wrap break-all text-gray-300 selection:bg-blue-500/30 selection:text-white" id="modal-body">
                    Carregando conteúdo...
                </div>
                
                <div class="p-4 border-t border-gray-800 shrink-0 text-xs text-gray-400 flex justify-between items-center bg-gray-900 rounded-b-2xl">
                    <div class="flex items-center gap-2">
                        <i data-lucide="hard-drive" class="w-4 h-4 text-gray-500"></i>
                        <span id="modal-size" class="font-medium">Tamanho: 0 KB</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i>
                        <span id="modal-date" class="font-medium">Modificado: -</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const state = {
            currentProject: null,
            logs: [],
            projects: [
                { id: 'supaco', name: 'Supaco', icon: 'database' },
                { id: 'sema', name: 'SEMA', icon: 'leaf' },
                { id: 'estagio_pdf', name: 'Estágio PDF', icon: 'file-text' },
                { id: 'protocolo_sead', name: 'Protocolo SEAD', icon: 'inbox' },
                { id: 'painel_cidadao', name: 'Painel do Cidadão', icon: 'users' },
                { id: 'app_mobile', name: 'App Mobile', icon: 'smartphone' }
            ]
        };

        const DOM = {
            projectList: document.getElementById('project-list'),
            logsContainer: document.getElementById('logs-container'),
            currentProjectTitle: document.getElementById('current-project-title'),
            searchInput: document.getElementById('search-input'),
            sortSelect: document.getElementById('sort-select'),
            limitSelect: document.getElementById('limit-select'),
            iconRefresh: document.getElementById('icon-refresh'),
            sidebar: document.getElementById('sidebar'),
            sidebarOverlay: document.getElementById('sidebar-overlay'),
            modal: document.getElementById('log-modal'),
            modalBackdrop: document.getElementById('modal-backdrop'),
            modalContent: document.getElementById('modal-content'),
            modalTitle: document.getElementById('modal-title'),
            modalBody: document.getElementById('modal-body'),
            modalSize: document.getElementById('modal-size'),
            modalDate: document.getElementById('modal-date')
        };

        const formatBytes = (bytes) => {
            if (bytes === 0) return '0 Bytes';
            const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        const formatDate = (dateString) => {
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            }).format(date);
        };

        const escapeHtml = (unsafe) => unsafe
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;").replace(/'/g, "&#039;");

        function renderProjects() {
            DOM.projectList.innerHTML = state.projects.map(p => `
                <button onclick="selectProject('${p.id}')" 
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-all duration-200 group relative
                        ${state.currentProject === p.id 
                            ? 'bg-blue-50 text-blue-700 font-medium' 
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'}">
                    <i data-lucide="${p.icon}" class="w-5 h-5 ${state.currentProject === p.id ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600'}"></i>
                    <span class="truncate z-10">${p.name}</span>
                    ${state.currentProject === p.id ? '<div class="absolute right-3 w-1.5 h-1.5 rounded-full bg-blue-600"></div>' : ''}
                </button>
            `).join('');
            lucide.createIcons();
        }

        function selectProject(projectId) {
            state.currentProject = projectId;
            const proj = state.projects.find(p => p.id === projectId);
            DOM.currentProjectTitle.textContent = proj ? proj.name : 'Projeto Selecionado';
            renderProjects();
            if(window.innerWidth < 1024) toggleSidebar(false);
            loadLogs();
        }

        async function loadLogs() {
            if (!state.currentProject) return;

            DOM.iconRefresh.classList.add('animate-spin');

            try {
                // Mock network delay
                await new Promise(r => setTimeout(r, 600));
                
                const response = await fetch(`api/logs.php?project=${encodeURIComponent(state.currentProject)}`);
                const data = await response.json();
                
                if (data.data) {
                    state.logs = data.data;
                    renderLogs();
                } else {
                    showError("Nenhum dado retornado da API.");
                }
            } catch (error) {
                console.error(error);
                showError("Erro ao carregar os logs. Verifique sua conexão ou se o arquivo api/logs.php existe.");
            } finally {
                DOM.iconRefresh.classList.remove('animate-spin');
            }
        }

        function renderLogs() {
            if (!state.logs || state.logs.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="col-span-full py-16 flex flex-col items-center justify-center text-gray-500 fade-in">
                        <div class="bg-gray-100 p-4 rounded-full mb-4">
                            <i data-lucide="inbox" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <p class="text-lg font-medium text-gray-700 mb-1">Nenhum log encontrado</p>
                        <p class="text-sm">Este projeto não possui arquivos de log no momento.</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            let filtered = [...state.logs];

            const term = DOM.searchInput.value.toLowerCase().trim();
            if (term) filtered = filtered.filter(l => l.file.toLowerCase().includes(term));

            const sortAsc = DOM.sortSelect.value === 'asc';
            filtered.sort((a, b) => {
                const tA = new Date(a.modified).getTime();
                const tB = new Date(b.modified).getTime();
                return sortAsc ? tA - tB : tB - tA;
            });

            const limit = DOM.limitSelect.value;
            if (limit !== 'all') filtered = filtered.slice(0, parseInt(limit, 10));

            if (filtered.length === 0) {
                DOM.logsContainer.innerHTML = `
                    <div class="col-span-full py-16 text-center text-gray-500 fade-in flex flex-col items-center">
                        <i data-lucide="search-x" class="w-10 h-10 mb-3 text-gray-300"></i>
                        <p class="font-medium text-gray-700">Nenhum log corresponde aos filtros.</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            DOM.logsContainer.innerHTML = filtered.map((log, index) => `
                <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all flex flex-col fade-in group" style="animation-delay: ${index * 0.05}s">
                    <div class="flex justify-between items-start mb-4 gap-3">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="bg-indigo-50 p-2 rounded-lg shrink-0 group-hover:bg-blue-50 transition-colors">
                                <i data-lucide="file-json" class="w-5 h-5 text-indigo-500 group-hover:text-blue-600 transition-colors"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 truncate tracking-tight text-base" title="${log.file}">${log.file}</h3>
                        </div>
                        <span class="bg-gray-50 text-gray-600 border border-gray-100 text-xs font-semibold px-2 py-1.5 rounded-md shrink-0 whitespace-nowrap">
                            ${formatBytes(log.size_bytes)}
                        </span>
                    </div>
                    
                    <div class="bg-gray-50/80 rounded-xl p-3.5 text-xs text-gray-600 font-mono mb-5 flex-1 break-all line-clamp-3 overflow-hidden text-ellipsis border border-gray-100 border-l-2 border-l-gray-300 shadow-inner group-hover:border-l-blue-400 transition-colors leading-relaxed">
                        ${escapeHtml(log.preview)}
                    </div>
                    
                    <div class="mt-auto flex items-center justify-between pt-4 border-t border-gray-100">
                        <div class="text-xs font-medium text-gray-500 flex items-center gap-1.5" title="${formatDate(log.modified)}">
                            <i data-lucide="clock" class="w-4 h-4 text-gray-400"></i>
                            <span class="truncate">${formatDate(log.modified)}</span>
                        </div>
                        <button onclick="openModal('${encodeURIComponent(JSON.stringify(log))}')" class="text-sm font-semibold text-blue-600 hover:text-blue-700 transition-all flex items-center gap-1.5 bg-blue-50 hover:bg-blue-100 px-3.5 py-1.5 rounded-lg active:scale-95">
                            <span>Ver detalhes</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            lucide.createIcons();
        }

        function showError(msg) {
            DOM.logsContainer.innerHTML = `
                <div class="col-span-full py-16 flex flex-col items-center justify-center text-red-500 fade-in">
                    <div class="bg-red-50 p-4 rounded-full mb-4">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-red-500"></i>
                    </div>
                    <p class="font-bold text-gray-800 text-lg mb-1">Ops, algo deu errado</p>
                    <p class="text-gray-500 text-sm max-w-md text-center">${msg}</p>
                </div>
            `;
            lucide.createIcons();
        }

        function openModal(logStrEnc) {
            try {
                const log = JSON.parse(decodeURIComponent(logStrEnc));
                DOM.modalTitle.textContent = log.file;
                DOM.modalBody.innerHTML = escapeHtml(log.preview);
                DOM.modalSize.textContent = formatBytes(log.size_bytes);
                DOM.modalDate.textContent = formatDate(log.modified);
                
                DOM.modal.classList.remove('hidden');
                
                requestAnimationFrame(() => {
                    DOM.modalBackdrop.classList.remove('opacity-0');
                    DOM.modalContent.classList.remove('opacity-0', 'scale-95');
                    DOM.modalContent.classList.add('scale-100');
                });
            } catch(e) { console.error(e); }
        }

        function closeModal() {
            DOM.modalBackdrop.classList.add('opacity-0');
            DOM.modalContent.classList.remove('scale-100');
            DOM.modalContent.classList.add('opacity-0', 'scale-95');
            
            setTimeout(() => {
                DOM.modal.classList.add('hidden');
            }, 300);
        }

        function copyLogContent() {
            const text = DOM.modalBody.textContent;
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.currentTarget;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-green-400"></i><span class="hidden sm:inline text-green-400">Copiado!</span>';
                lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }, 2000);
            }).catch(e => console.error('Failed to copy', e));
        }

        function toggleSidebar(forceState) {
            const isHidden = DOM.sidebar.classList.contains('-translate-x-full');
            const toShow = forceState !== undefined ? forceState : isHidden;

            if (toShow) {
                DOM.sidebar.classList.remove('-translate-x-full');
                DOM.sidebarOverlay.classList.remove('hidden');
                requestAnimationFrame(() => DOM.sidebarOverlay.classList.remove('opacity-0'));
            } else {
                DOM.sidebar.classList.add('-translate-x-full');
                DOM.sidebarOverlay.classList.add('opacity-0');
                setTimeout(() => DOM.sidebarOverlay.classList.add('hidden'), 300);
            }
        }

        DOM.searchInput.addEventListener('input', () => setTimeout(renderLogs, 300));
        DOM.sortSelect.addEventListener('change', renderLogs);
        DOM.limitSelect.addEventListener('change', renderLogs);
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !DOM.modal.classList.contains('hidden')) closeModal();
        });

        renderProjects();
    </script>
</body>
</html>
