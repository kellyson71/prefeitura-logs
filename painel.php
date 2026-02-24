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
    <title>SYS LOGS // HUD GLOBAL</title>
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        vs: { dim: '#3a6875', }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="flex h-screen overflow-hidden flex-col">

    <!-- SCI-FI TOPBAR -->
    <header class="h-14 border-b border-[var(--panel-border)] shrink-0 flex items-center justify-between px-6 z-20 bg-[var(--panel-bg)] backdrop-blur-md">
        <div class="flex items-center gap-3">
            <div class="px-2 py-1 bg-[var(--neon-cyan)]/10 border border-[var(--neon-cyan)]/30 text-[var(--neon-cyan)] font-tech text-xs tracking-widest">
                root@terminal:~#
            </div>
            <h1 class="text-lg font-bold tracking-widest text-[#d3ebed] uppercase">Logs <span class="text-[var(--text-dim)] font-normal">System</span></h1>
        </div>

        <div class="flex items-center gap-4">
            <div id="global-spinner" class="hidden items-center gap-2 text-xs font-tech text-[var(--neon-cyan)] glitch-text">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> SYNCING...
            </div>
            
            <button onclick="window.loadLogsFromApi?.()" class="text-[var(--text-dim)] hover:text-[var(--neon-cyan)] transition-colors p-2" title="Force Sync">
                <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
            </button>
            
            <div class="flex items-center gap-2 pl-4 border-l border-[var(--panel-border)]">
                <div class="text-xs font-tech text-[var(--text-dim)] uppercase tracking-wider">
                    USER: <span class="text-[var(--neon-cyan)]"><?= $_SESSION['username'] ?></span>
                </div>
                <a href="logout.php" class="text-[var(--text-dim)] hover:text-[var(--neon-red)] transition-colors ml-3" title="Disconnect">
                    <i data-lucide="power" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- SIDEBAR PROJETOS -->
        <aside class="w-64 border-r border-[var(--panel-border)] flex flex-col shrink-0 z-10 bg-[var(--bg-dark)]/80 relative">
            <div class="px-5 py-4 text-[10px] font-bold text-[var(--text-dim)] uppercase tracking-widest border-b border-[var(--panel-border)]">
                CONNECTION TARGETS
            </div>
            <nav class="flex-1 overflow-y-auto p-2 space-y-1" id="project-list"></nav>
        </aside>

        <!-- MAIN TERMINAL -->
        <main class="flex-1 flex flex-col relative min-w-0 bg-[var(--bg-dark)]">
            
            <!-- GLOBAL DASHBOARD (Nova Tela Inicial) -->
            <div id="view-global-dashboard" class="hidden absolute inset-0 flex-col overflow-y-auto z-20 p-6 md:p-10 fade-in w-full h-full">
                
                <div class="max-w-[1400px] mx-auto w-full flex flex-col xl:flex-row gap-8">
                    
                    <!-- Coluna Esquerda: Overview e Projetos -->
                    <div class="flex-1 shrink-0 flex flex-col">
                        
                        <div class="flex items-end justify-between border-b border-[var(--panel-border)] pb-4 mb-8">
                            <div>
                                <h2 class="text-3xl font-tech text-[var(--neon-cyan)] tracking-widest uppercase glitch-text mb-1">GLOBAL OVERVIEW</h2>
                                <p class="text-xs font-tech text-[var(--text-dim)] uppercase tracking-widest">Monitoramento simultâneo de todos os Nodes do Servidor</p>
                            </div>
                            <div class="text-right flex items-center gap-6 hidden sm:flex">
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-1">Total Files</span>
                                    <span class="text-2xl font-tech text-white leading-none" id="m-stat-files">0</span>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-1">Volumetria</span>
                                    <span class="text-2xl font-tech text-white leading-none" id="m-stat-size">0 B</span>
                                </div>
                                <div class="flex flex-col items-end border-l border-[var(--neon-red)]/50 pl-6">
                                    <span class="text-[10px] text-[var(--neon-red)] font-tech uppercase mb-1">Global Exceptions</span>
                                    <span class="text-3xl font-tech text-[var(--neon-red)] leading-none text-shadow-red" id="m-stat-errs">0</span>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-xs text-[var(--text-dim)] uppercase font-tech tracking-widest mb-4">SYSTEM GRID LAYER</h3>
                        
                        <!-- Aqui os cards globais entram dinamicamente -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-10" id="global-grid-projects">
                            <!-- Populated by JS -->
                        </div>

                        <h3 class="text-xs text-[var(--text-dim)] uppercase font-tech tracking-widest mb-4">LATEST SYSTEM ACTIVITY</h3>
                        <!-- Lista unificada de ultimos logs -->
                        <div id="global-recent-logs" class="flex flex-col gap-2 mb-10 border border-[var(--panel-border)] bg-[var(--panel-bg)] p-3 h-64 overflow-y-auto">
                            <!-- JS appends recent logs (top 15) -->
                        </div>

                        <!-- Toolkit / Ferramentas Inferiores -->
                        <h3 class="text-xs text-[var(--text-dim)] uppercase font-tech tracking-widest mb-4">UTILITY COMMANDS</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div onclick="alert('Funcionalidade em desenvolvimento')" class="hud-panel p-5 border border-[var(--panel-border)] hover:border-[var(--neon-orange)] transition-colors cursor-pointer group bg-orange-950/20">
                                <i data-lucide="trash-2" class="w-6 h-6 text-[var(--neon-orange)] mb-3 group-hover:scale-110 transition-transform"></i>
                                <h4 class="font-tech text-sm text-[var(--text-main)] uppercase tracking-widest mb-2">Purge Old Logs</h4>
                                <p class="text-[11px] font-tech text-[var(--text-dim)]">Limpar arquivos com mais de 30 dias para liberar disco.</p>
                            </div>
                            <div onclick="window.initApp?.()" class="hud-panel p-5 border border-[var(--panel-border)] hover:border-[var(--neon-green)] transition-all cursor-pointer group bg-[var(--neon-green)]/10">
                                <i data-lucide="refresh-cw" class="w-6 h-6 text-[var(--neon-green)] mb-3 group-hover:animate-spin"></i>
                                <h4 class="font-tech text-sm text-[var(--text-main)] uppercase tracking-widest mb-2">Force Re-Sync</h4>
                                <p class="text-[11px] font-tech text-[var(--text-dim)]">Varrer os diretórios do Linux forçando re-leitura bruta agora.</p>
                            </div>
                            <div onclick="alert('Sistema OK. Todos os módulos online e respondendo.')" class="hud-panel p-5 border border-[var(--panel-border)] hover:border-[var(--neon-cyan)] transition-colors cursor-pointer group bg-[#061118]">
                                <i data-lucide="cpu" class="w-6 h-6 text-[var(--neon-cyan)] mb-3 group-hover:scale-110 transition-transform"></i>
                                <h4 class="font-tech text-sm text-[var(--text-main)] uppercase tracking-widest mb-2">Engine Health</h4>
                                <p class="text-[11px] font-tech text-[var(--text-dim)]">Diagnóstico rápido dos serviços core base (PHP, Apache).</p>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Coluna Direita: Live Console -->
                    <div class="w-full xl:w-[450px] shrink-0 hud-panel flex flex-col border border-[var(--panel-border)] shadow-[0_0_15px_rgba(0,240,255,0.05)] h-auto min-h-[600px] xl:max-h-[85vh] xl:sticky xl:top-0">
                         <div class="h-10 border-b border-[var(--panel-border)] flex items-center justify-between px-4 bg-[#0a0f12] shrink-0">
                              <span class="text-[11px] font-tech text-[var(--neon-green)] tracking-widest uppercase flex items-center gap-2">
                                   <i data-lucide="radio" class="w-3 h-3 animate-pulse"></i> LIVE STREAM INTERCEPT
                              </span>
                              <span class="text-[10px] text-[var(--text-dim)] font-tech border border-[var(--panel-border)] px-1 relative">READ-ONLY</span>
                         </div>
                         <div id="global-live-terminal" class="flex-1 overflow-y-auto p-4 flex flex-col gap-1.5 bg-[#050505] text-[11px] font-mono leading-relaxed pb-4 custom-scrollbar">
                              <div class="text-[var(--text-dim)] animate-pulse font-tech text-xs tracking-widest">Waiting for signals...</div>
                         </div>
                    </div>

                </div>
            </div>

            <!-- INDIVIDUAL PROJECT VIEW -->
            <div id="view-project" class="hidden flex-col h-full z-10 w-full relative">
                
                <!-- HUD Tabs -->
                <div class="h-12 border-b border-[var(--panel-border)] flex items-end px-6 gap-8 bg-[var(--bg-dark)]/90">
                    <div class="flex items-center gap-2 text-sm text-[var(--neon-cyan)] mb-2.5 min-w-[200px] font-tech" id="current-project-title">
                        TARGET: NULL
                    </div>

                    <button class="tab-btn active uppercase text-xs font-bold tracking-widest pb-3 px-2 flex items-center gap-2" id="tab-btn-dashboard">
                        <i data-lucide="activity" class="w-4 h-4"></i> Overview
                    </button>
                    <button class="tab-btn uppercase text-xs font-bold tracking-widest pb-3 px-2 flex items-center gap-2" id="tab-btn-logs">
                        <i data-lucide="terminal" class="w-4 h-4"></i> Engine Logs
                    </button>
                </div>

                <!-- TABS CONTENT -->
                <div class="flex-1 overflow-hidden relative p-1">
                    
                    <!-- Aba: OVERVIEW (Health HUD) -->
                    <div id="tab-content-dashboard" class="tab-content w-full h-full overflow-y-auto p-4 sm:p-8 active fade-in flex">
                        
                        <div class="w-full max-w-6xl mx-auto flex flex-col lg:flex-row gap-8">
                            
                            <!-- Left: Health Status Big UI -->
                            <div class="hud-panel p-8 w-full lg:w-1/3 flex flex-col items-center justify-center shrink-0">
                                <h3 class="text-xs text-[var(--text-dim)] uppercase font-tech tracking-widest w-full text-center border-b border-[var(--panel-border)] pb-2 mb-8">NODE HEALTH</h3>
                                
                                <div class="relative flex items-center justify-center mb-6">
                                    <div id="health-ring" class="w-32 h-32 rounded-full border-4 flex items-center justify-center border-[var(--text-dim)] transition-all duration-700">
                                        <div id="health-score" class="text-4xl font-tech font-bold text-[var(--text-main)] transition-colors duration-500">
                                            --%
                                        </div>
                                    </div>
                                    <!-- Decorative rings -->
                                    <div class="absolute inset-[-10px] border border-[var(--panel-border)] rounded-full animate-spin" style="animation-duration: 15s;"></div>
                                </div>
                                
                                <div id="health-status" class="text-sm font-tech font-bold text-[var(--text-main)] tracking-widest uppercase transition-colors">
                                    AWAITING SCAN
                                </div>
                            </div>

                            <!-- Right: Stats Cluster -->
                            <div class="flex-1 grid grid-cols-2 gap-4">
                                
                                <div class="hud-panel p-5 flex flex-col h-32">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-auto opacity-80 flex items-center gap-2">
                                        <i data-lucide="database" class="w-3 h-3 text-[var(--neon-cyan)]"></i> LOCAL FILES
                                    </span>
                                    <div class="text-4xl font-tech font-normal text-[var(--text-main)]" id="stat-total-logs">0</div>
                                </div>

                                <div class="hud-panel p-5 flex flex-col h-32">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-auto opacity-80 flex items-center gap-2">
                                        <i data-lucide="hard-drive" class="w-3 h-3 text-[var(--hl-string)]"></i> STORAGE VOL
                                    </span>
                                    <div class="text-4xl font-tech font-normal text-[var(--text-main)]" id="stat-total-size">0 B</div>
                                </div>

                                <div class="hud-panel p-5 flex flex-col h-32 border-l-2 border-[var(--neon-red)]">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-auto opacity-80 flex items-center gap-2">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 text-[var(--neon-red)]"></i> NODE EXCEPTIONS
                                    </span>
                                    <div class="text-4xl font-tech font-bold text-[var(--neon-red)]" id="stat-errors">0</div>
                                </div>

                                <div class="hud-panel p-5 flex flex-col h-32 border-l-2 border-[var(--neon-orange)]">
                                    <span class="text-[10px] text-[var(--text-dim)] font-tech uppercase mb-auto opacity-80 flex items-center gap-2">
                                        <i data-lucide="alert-circle" class="w-3 h-3 text-[var(--neon-orange)]"></i> SYS WARNINGS
                                    </span>
                                    <div class="text-4xl font-tech font-bold text-[var(--neon-orange)]" id="stat-warns">0</div>
                                </div>
                                
                                <div class="col-span-2 hud-panel p-4 flex items-center justify-between">
                                    <div class="text-xs font-tech text-[var(--text-dim)] uppercase">Action required</div>
                                    <button onclick="document.getElementById('tab-btn-logs').click()" class="text-xs font-tech px-4 py-1.5 bg-[var(--neon-cyan)]/10 text-[var(--neon-cyan)] border border-[var(--neon-cyan)]/50 hover:bg-[var(--neon-cyan)] hover:text-black transition-all">
                                        INSPECT LOG FILES >
                                    </button>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Aba: ENGINE LOGS -->
                    <div id="tab-content-logs" class="tab-content w-full h-full flex-col md:flex-row">
                        
                        <!-- Col 1: Files -->
                        <div class="w-full md:w-80 border-r border-[var(--panel-border)] bg-[var(--panel-bg)] flex flex-col shrink-0 flex-none gap-2 px-2 py-3">
                            <div class="relative w-full mb-1">
                                <i data-lucide="search" class="w-3 h-3 absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-dim)]"></i>
                                <!-- Correção da Cor Branca Aplicada AQUI. Background Preto com opacidade e fonte tech -->
                                <input type="text" id="search-input" placeholder="Query Node..." class="w-full bg-black/40 border border-[var(--panel-border)] text-xs font-tech text-[var(--text-main)] pl-8 pr-3 py-2 outline-none focus:border-[var(--neon-cyan)] focus:shadow-[0_0_8px_rgba(0,240,255,0.2)] transition-all uppercase placeholder-[var(--text-dim)]">
                            </div>
                            <div class="flex-1 overflow-y-auto pr-1 space-y-px" id="logs-container"></div>
                        </div>

                        <!-- Col 2: The IDE Window -->
                        <div class="flex-1 flex flex-col min-w-0 relative bg-[var(--bg-dark)]">
                            
                            <div id="console-empty" class="absolute inset-0 flex flex-col items-center justify-center text-[var(--text-dim)] z-10 transition-all font-tech uppercase">
                                <i data-lucide="code" class="w-16 h-16 opacity-30 mb-4 animate-pulse"></i>
                                <p class="tracking-widest opacity-60">SELECT FILE TO STREAM</p>
                            </div>

                            <div id="console-active" class="hidden flex-col h-full z-20 slide-in-right bg-[#050505]">
                                
                                <!-- File Toolbar HUD Style -->
                                <div class="h-10 border-b border-[var(--panel-border)] flex items-center justify-between px-4 shrink-0 bg-[#0a0f12]">
                                    <div class="flex items-center gap-2 max-w-[60%] font-tech text-xs tracking-wider" id="console-title">
                                        <!-- Titulo gerado em js -->
                                    </div>
                                    
                                    <div class="flex items-center gap-4">
                                        <div id="console-actions"></div>
                                        
                                        <button onclick="window.closeConsole()" class="p-1 text-[var(--text-dim)] hover:text-[var(--neon-cyan)] transition-colors">
                                            <i data-lucide="x-square" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex-1 overflow-auto py-2" id="console-body">
                                    <!-- Codigo limpo aqui -->
                                </div>
                                
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            
        </main>
    </div>

    <script src="assets/js/app.js"></script>

</body>
</html>
