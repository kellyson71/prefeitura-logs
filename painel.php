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
    <title>Painel de Logs Analítico</title>
    
    <!-- Evitar Erros Favicon 404 -->
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['"Fira Code"', 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <!-- Ícones -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- CSS Próprio (Tema VSCode) -->
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="flex h-screen overflow-hidden antialiased flex-col">

    <!-- Topbar -->
    <header class="h-14 bg-vs-panel border-b border-vs-border shrink-0 flex items-center justify-between px-4 z-20">
        <div class="flex items-center gap-3">
            <div class="p-1.5 bg-[#424242] rounded-lg">
                <i data-lucide="terminal-square" class="w-5 h-5 text-gray-200"></i>
            </div>
            <h1 class="text-sm font-semibold tracking-wide text-gray-200">Inspetor de Logs</h1>
        </div>

        <div class="flex items-center gap-4">
            <div id="global-spinner" class="hidden items-center gap-2 text-xs font-medium text-vs-muted">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-vs-blue"></i> Lendo disco...
            </div>
            
            <div class="h-6 w-px bg-vs-border"></div>

            <button onclick="window.loadLogsFromApi()" class="text-vs-muted hover:text-vs-text transition-colors p-1.5 rounded-md hover:bg-[#37373d]" title="Sincronizar">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
            
            <div class="flex items-center gap-2 pl-2 border-l border-vs-border">
                <div class="w-7 h-7 rounded-full bg-[#007acc] flex items-center justify-center text-xs font-bold text-white shadow-sm ring-2 ring-vs-bg">
                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                </div>
                <a href="logout.php" class="text-vs-muted hover:text-[#f44747] transition-colors ml-2" title="Sair do sistema">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar Esquerda (Projetos) -->
        <aside class="w-64 bg-vs-sidebar border-r border-vs-border flex flex-col shrink-0 z-10 shadow-[2px_0_8px_rgba(0,0,0,0.2)]">
            <div class="px-4 py-3 text-xs font-bold text-vs-muted uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="briefcase" class="w-3.5 h-3.5"></i> PROJETOS
            </div>
            <nav class="flex-1 overflow-y-auto pt-1 space-y-[1px]" id="project-list">
                <!-- Javascript Renders Projects Here -->
            </nav>
        </aside>

        <!-- Main Content Central -->
        <main class="flex-1 flex flex-col relative min-w-0 bg-vs-bg shadow-inner">
            
            <!-- Estado 1: Vazio (Nenhum Projeto) -->
            <div id="view-empty" class="absolute inset-0 flex flex-col items-center justify-center fade-in z-20">
                <div class="p-6 rounded-xl border border-vs-border bg-vs-panel flex flex-col items-center text-center shadow-2xl max-w-sm">
                    <i data-lucide="monitor" class="w-16 h-16 text-[#4a4a4a] mb-4"></i>
                    <h2 class="text-lg font-semibold text-vs-text mb-2">Painel de Sistemas</h2>
                    <p class="text-sm text-vs-muted">Selecione uma pasta ou projeto na barra lateral para analisar sua saúde e inspecionar logs.</p>
                </div>
            </div>

            <!-- Estado 2: Projeto Selecionado (Tabs System) -->
            <div id="view-project" class="hidden flex-col h-full z-10 w-full fade-in relative">
                
                <!-- Navbar das Tabs -->
                <div class="h-12 bg-vs-panel flex items-end px-6 border-b border-vs-border shrink-0 justify-between">
                    <div class="flex items-center gap-6 h-full">
                        <div class="flex items-center gap-2 text-sm text-vs-text mb-2 min-w-[150px]">
                            <i data-lucide="folder" class="w-4 h-4 text-vs-blue"></i>
                            <span id="current-project-title" class="font-semibold truncate tracking-wider">Projeto</span>
                        </div>

                        <!-- Botões Aba -->
                        <div class="flex items-end h-full">
                            <button class="tab-btn active font-medium text-sm pb-2.5 px-3 flex items-center gap-2" id="tab-btn-dashboard">
                                <i data-lucide="pie-chart" class="w-4 h-4"></i> Resumo
                            </button>
                            <button class="tab-btn font-medium text-sm pb-2.5 px-3 flex items-center gap-2" id="tab-btn-logs">
                                <i data-lucide="list-tree" class="w-4 h-4"></i> Detalhamento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CONTEÚDO TABS -->
                <div class="flex-1 overflow-hidden relative">
                    
                    <!-- Tab 1: DASHBOARD (Resumo do Projeto) -->
                    <div id="tab-content-dashboard" class="tab-content w-full h-full overflow-y-auto p-6 md:p-8 active fade-in bg-vs-bg">
                        <div class="max-w-5xl mx-auto flex flex-col gap-6">
                            <h2 class="text-xl font-light text-vs-text">Visão Geral do Diretório</h2>
                            
                            <!-- Health Status Banner -->
                            <div id="health-banner" class="w-full flex items-center p-4 rounded-xl border opacity-90 transition-all duration-300 gap-4 shadow-lg hidden">
                                <div id="health-icon-bg" class="w-12 h-12 rounded-full flex items-center justify-center shrink-0">
                                    <i id="health-icon" data-lucide="activity" class="w-6 h-6 text-white"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span id="health-title" class="text-lg font-bold text-white tracking-wide">Calculando...</span>
                                    <span id="health-desc" class="text-sm text-white/80">Analisando estabilidade baseada em logs recentes.</span>
                                </div>
                            </div>
                            
                            <!-- Cards Estatisticos -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                                <div class="bg-vs-panel border border-vs-border/50 p-5 rounded-lg flex flex-col">
                                    <span class="text-xs text-vs-muted font-bold uppercase mb-2 flex items-center gap-2">
                                        <i data-lucide="files" class="w-4 h-4 text-vs-blue"></i> Arquivos
                                    </span>
                                    <div class="text-3xl font-light text-vs-text mb-1" id="stat-total-logs">0</div>
                                </div>

                                <div class="bg-vs-panel border border-vs-border/50 p-5 rounded-lg flex flex-col">
                                    <span class="text-xs text-vs-muted font-bold uppercase mb-2 flex items-center gap-2">
                                        <i data-lucide="hard-drive" class="w-4 h-4 text-[#ce9178]"></i> Tamanho Ocupado
                                    </span>
                                    <div class="text-3xl font-light text-vs-text mb-1" id="stat-total-size">0 B</div>
                                </div>

                                <div class="bg-vs-panel border border-vs-border/50 p-5 rounded-lg flex flex-col">
                                    <span class="text-xs text-vs-muted font-bold uppercase mb-2 flex items-center gap-2">
                                        <i data-lucide="alert-octagon" class="w-4 h-4 text-[#f44747]"></i> Erros Fatais
                                    </span>
                                    <div class="text-2xl font-light text-[#f44747] mb-1 leading-tight"><span id="stat-errors">0</span> rastreados</div>
                                </div>

                                <div class="bg-vs-panel border border-vs-border/50 p-5 rounded-lg flex flex-col">
                                    <span class="text-xs text-vs-muted font-bold uppercase mb-2 flex items-center gap-2">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-[#d7ba7d]"></i> Avisos (Warnings)
                                    </span>
                                    <div class="text-2xl font-light text-[#d7ba7d] mb-1 leading-tight"><span id="stat-warns">0</span> rastreados</div>
                                </div>
                            </div>
                            
                            <div class="p-6 border border-dashed border-vs-border/60 rounded-xl bg-vs-panel/30 flex items-center gap-4 text-vs-muted mt-4">
                                <i data-lucide="mouse-pointer-click" class="w-6 h-6 shrink-0 opacity-60"></i>
                                <div class="text-sm max-w-2xl">
                                    Vá até a aba de <strong class="text-vs-text">Detalhamento</strong> para ler as falhas em formatação de depurador (foco nos logs mais recentes) e utilizar filtros de Isolamentos (Ver apenas Erros / Apenas Avisos) instantâneos para agilizar a investigação.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Tab 2: LISTA E CONSOLE DE LOGS -->
                    <div id="tab-content-logs" class="tab-content w-full h-full fade-in flex-col md:flex-row bg-vs-bg">
                        
                        <!-- Coluna 1 da Tab 2 (Cards e Busca) -->
                        <div class="w-full md:w-80 border-r border-vs-border bg-vs-panel/30 flex flex-col shrink-0 flex-none max-h-[40vh] md:max-h-full">
                            <div class="p-4 border-b border-vs-border bg-[#191919]">
                                <div class="relative w-full">
                                    <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-vs-muted"></i>
                                    <input type="text" id="search-input" placeholder="Buscar em nomes..." class="w-full bg-vs-bg border border-vs-border text-xs text-vs-text pl-8 pr-3 py-2 rounded outline-none focus:border-vs-blue focus:ring-1 focus:ring-vs-blue transition-all">
                                </div>
                            </div>
                            
                            <!-- Lista de Arquivos -->
                            <div class="flex-1 overflow-y-auto p-2 space-y-2" id="logs-container">
                                <!-- Cards JS Renders -->
                            </div>
                        </div>

                        <!-- Coluna 2 da Tab 2 (Visualizador) -->
                        <div class="flex-1 flex flex-col min-w-0 relative bg-vs-bg">
                            
                            <!-- Null State -->
                            <div id="console-empty" class="absolute inset-0 flex flex-col items-center justify-center text-vs-muted z-10">
                                <i data-lucide="code-2" class="w-16 h-16 opacity-10 mb-3"></i>
                                <p class="text-sm">Selecione um arquivo de log na lista lateral.</p>
                            </div>

                            <!-- Active State Console -->
                            <div id="console-active" class="hidden flex-col h-full z-20 bg-vs-bg">
                                
                                <!-- Tool/Action Bar Superior do Console -->
                                <div class="h-12 border-b border-vs-border flex items-center justify-between px-4 shrink-0 bg-vs-panel shadow-sm relative pt-1">
                                    
                                    <!-- Abinha File Name -->
                                    <div class="bg-vs-bg border border-vs-border border-b-0 rounded-t-lg px-3 py-1.5 flex items-center gap-2 group min-w-0 max-w-xs self-end h-[34px] relative -bottom-[1px]">
                                        <div class="w-2 h-2 rounded-full bg-vs-blue/50"></div>
                                        <span id="console-title" class="text-xs font-mono text-vs-text truncate">arquivo.log</span>
                                        <button class="opacity-0 group-hover:opacity-100 hover:bg-white/10 rounded ml-1 transition-opacity" onclick="window.closeConsole()">
                                            <i data-lucide="x" class="w-3 h-3 text-vs-muted"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Filtros e Actions -->
                                    <div class="flex items-center gap-3">
                                        <!-- Controle de Filtros In-Console (Visão) -->
                                        <div class="flex items-center bg-[#1e1e1e] border border-vs-border rounded text-[11px] font-medium overflow-hidden font-sans">
                                            <button onclick="window.setConsoleFilter('all')" id="filter-all" class="px-3 py-1.5 hover:bg-white/5 text-white bg-[#37373d]">Tudo</button>
                                            <div class="w-px h-4 bg-vs-border"></div>
                                            <button onclick="window.setConsoleFilter('error')" id="filter-error" class="px-3 py-1.5 hover:bg-white/5 text-vs-muted transition-colors flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-[#f44747]"></div> Erros</button>
                                            <div class="w-px h-4 bg-vs-border"></div>
                                            <button onclick="window.setConsoleFilter('warn')" id="filter-warn" class="px-3 py-1.5 hover:bg-white/5 text-vs-muted transition-colors flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-[#d7ba7d]"></div> Avisos</button>
                                        </div>

                                        <!-- Copy/Scroll -->
                                        <div class="h-4 w-px bg-vs-border"></div>

                                        <button onclick="window.scrollToBottom()" class="text-vs-muted hover:text-vs-text text-[11px] flex gap-1 items-center px-1" title="Ir para as últimas linhas">
                                            <i data-lucide="arrow-down-to-line" class="w-3.5 h-3.5"></i> Fim
                                        </button>
                                        <button onclick="window.copyConsoleOutput()" id="btn-copy" class="text-vs-muted hover:text-vs-text text-[11px] flex gap-1 items-center px-1">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- The Code Window Body -->
                                <div class="flex-1 overflow-auto bg-vs-bg py-2 font-mono text-[13px] leading-relaxed selection:bg-[#264f78]" id="console-body">
                                    <!-- Render via API -->
                                </div>
                            </div>
                            
                        </div>

                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Scripts do Sistema -->
    <script src="assets/js/app.js"></script>

</body>
</html>
