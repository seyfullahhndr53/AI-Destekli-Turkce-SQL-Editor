class ModernSQLEditor {
    constructor() {
        this.csrfToken = '';
        this.tables = {};
        this.currentTheme = 'light';
        this.queryHistory = [];
        this.currentTableFilter = '';
        this.isLoading = false;
        this.stats = {
            totalQueries: 0,
            totalTables: 0
        };

        this.init();
    }

    async init() {
        this.showLoadingScreen();
        await this.loadCSRFToken();
        this.setupTheme();
        this.bindEvents();
        this.setupQuestionAnalysis();
        await this.loadTables();
        this.setupUI();
        this.hideLoadingScreen();
    }

    showLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.style.display = 'flex';
        }
    }

    hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            setTimeout(() => {
                loadingScreen.classList.add('fade-out');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 500);
            }, 1000);
        }
    }

    async loadCSRFToken() {
        try {
            this.updateConnectionStatus('connecting');
            const response = await fetch('get-csrf-token.php');
            const data = await response.json();
            this.csrfToken = data.token;
            this.updateConnectionStatus('connected');
        } catch (error) {
            console.error('CSRF token yüklenirken hata:', error);
            this.updateConnectionStatus('error');
        }
    }

    updateConnectionStatus(status) {
        const statusDot = document.getElementById('connectionStatus');
        const statusText = document.querySelector('.status-text');

        if (statusDot && statusText) {
            statusDot.className = `status-dot ${status}`;

            const statusMessages = {
                connecting: 'Bağlanıyor...',
                connected: 'Bağlandı',
                error: 'Bağlantı Hatası'
            };

            statusText.textContent = statusMessages[status] || 'Bilinmeyen';
        }
    }

    setupTheme() {
        const savedTheme = localStorage.getItem('sqlEditor_theme') || 'light';
        this.setTheme(savedTheme);
    }

    setTheme(theme) {
        this.currentTheme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('sqlEditor_theme', theme);

        const themeIcon = document.querySelector('.theme-icon');
        if (themeIcon) {
            themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    bindEvents() {

        document.getElementById('themeToggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });

        document.getElementById('btnCreateDatabase')?.addEventListener('click', () => {
            this.hideModal('databaseNotFoundModal');
            this.showDatabaseGenerator();
        });

        document.getElementById('runRandomDatabaseGenerator')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showDatabaseGenerator();
        });

        document.getElementById('runResetDatabase')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showDatabaseReset();
        });

        document.getElementById('btnRun')?.addEventListener('click', () => {
            this.executeQuery();
        });

        document.getElementById('formatSQL')?.addEventListener('click', () => {
            this.formatSQL();
        });

        document.getElementById('clearEditor')?.addEventListener('click', () => {
            this.clearEditor();
        });

        document.getElementById('exportResults')?.addEventListener('click', () => {
            this.exportResults();
        });

        document.getElementById('tableSearch')?.addEventListener('input', (e) => {
            this.filterTables(e.target.value);
        });

        document.querySelectorAll('.example-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const query = btn.getAttribute('data-query');
                this.setQuery(query);
            });
        });

        const sqlEditor = document.getElementById('txtSqlQuery');
        if (sqlEditor) {

            sqlEditor.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.keyCode === 13) {
                    this.executeQuery();
                }
            });

            sqlEditor.addEventListener('input', () => {
                this.updateQueryStats();
            });

            this.setupAutoResize(sqlEditor);
        }

        this.setupFAB();

        window.addEventListener('beforeunload', () => {
            this.saveQueryHistory();
        });

        this.setupResponsiveSidebar();
    }

    setupResponsiveSidebar() {
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            if (sidebar && mainContent) {

            }
        }
    }

    setupFAB() {
        const fabMain = document.getElementById('fabMain');
        const fabContainer = document.querySelector('.fab-container');

        if (fabMain && fabContainer) {
            fabMain.addEventListener('click', () => {
                fabContainer.classList.toggle('active');
            });

            document.getElementById('fabClearAll')?.addEventListener('click', () => {
                this.clearAll();
                fabContainer.classList.remove('active');
            });

            document.getElementById('fabExamples')?.addEventListener('click', () => {
                this.showExamples();
                fabContainer.classList.remove('active');
            });

            document.getElementById('fabHelp')?.addEventListener('click', () => {
                this.showHelp();
                fabContainer.classList.remove('active');
            });

            document.addEventListener('click', (e) => {
                if (!fabContainer.contains(e.target)) {
                    fabContainer.classList.remove('active');
                }
            });
        }
    }

    setupUI() {

        if (typeof hljs !== 'undefined') {
            hljs.initHighlightingOnLoad();
        }

        document.querySelectorAll('.modal .btn-success').forEach(btn => {
            if (btn.onclick) return;
            btn.addEventListener('click', () => location.reload());
        });

        this.loadQueryHistory();

        this.updateStats();

        this.setupTableListVirtualization();
    }

    async loadTables() {
        try {
            const response = await fetch('get-tables.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();

            if (result.success) {
                this.tables = result.data;
                this.stats.totalTables = Object.keys(this.tables).length;
                this.renderTables();
                this.updateStats();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Tables yüklenirken hata:', error);
            this.showModal('databaseNotFoundModal');
        }
    }

    renderTables(filter = '') {
        const tablesContainer = document.getElementById('tables');
        const emptyState = document.getElementById('tableListEmpty');

        if (!tablesContainer) return;

        tablesContainer.innerHTML = '';

        const filteredTables = Object.entries(this.tables).filter(([tableName]) =>
            tableName.toLowerCase().includes(filter.toLowerCase())
        );

        if (filteredTables.length === 0) {
            if (emptyState) {
                emptyState.style.display = 'block';
                emptyState.innerHTML = filter ? 
                    `<div class="empty-icon">🔍</div><p>"${filter}" için sonuç bulunamadı</p>` :
                    `<div class="empty-icon">📭</div><p>Henüz tablo yüklenmedi</p>`;
            }
            return;
        }

        if (emptyState) {
            emptyState.style.display = 'none';
        }

        filteredTables.forEach(([tableName, count]) => {
            const listItem = this.createTableListItem(tableName, count);
            tablesContainer.appendChild(listItem);
        });

        const tableCount = document.getElementById('tableCount');
        if (tableCount) {
            tableCount.textContent = filteredTables.length;
        }
    }

    createTableListItem(tableName, count) {
        const li = document.createElement('li');

        const tableItem = document.createElement('a');
        tableItem.href = '#';
        tableItem.className = 'table-item';
        tableItem.setAttribute('data-table', tableName);

        tableItem.innerHTML = `
            <div class="table-name">
                <span class="table-icon">${this.getTableIcon(tableName)}</span>
                <span>${this.escapeHtml(tableName)}</span>
            </div>
            <span class="row-count">${this.formatNumber(count)}</span>
        `;

        tableItem.addEventListener('click', (e) => {
            e.preventDefault();
            this.selectTable(tableName, tableItem);
        });

        li.appendChild(tableItem);
        return li;
    }

    getTableIcon(tableName) {
        const iconMap = {
            'Musteriler': '👥',
            'Calisanlar': '👨‍💼',
            'Urunler': '🛍️',
            'Siparisler': '📋',
            'Kategoriler': '📂',
            'Saticilar': '🏪',
            'KargoFirmalari': '🚚',
            'SiparisDetaylari': '📝'
        };
        return iconMap[tableName] || '📊';
    }

    selectTable(tableName, element) {

        document.querySelectorAll('.table-item').forEach(item => {
            item.classList.remove('active');
        });

        element.classList.add('active');

        const cleanTableName = tableName.replace('tbl', '');
        const query = `SELECT * FROM ${cleanTableName} LIMIT 10;`;
        this.setQuery(query);
    }

    filterTables(filter) {
        this.currentTableFilter = filter;
        this.renderTables(filter);
    }

    setupTableListVirtualization() {

    }

    setQuery(query) {
        const editor = document.getElementById('txtSqlQuery');
        if (editor) {
            editor.value = query;
            editor.focus();
            this.updateQueryStats();

            editor.classList.add('fade-in');
            setTimeout(() => editor.classList.remove('fade-in'), 300);
        }
    }

    async executeQuery() {
        if (this.isLoading) return;

        const queryString = document.getElementById('txtSqlQuery')?.value?.trim();

        if (!queryString) {
            this.showError('Lütfen bir SQL sorgusu girin');
            return;
        }

        this.setLoading(true);

        try {
            const formData = new FormData();
            formData.append('queryString', queryString);
            formData.append('csrf_token', this.csrfToken);

            const response = await fetch('simple-query-executer.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {

                const actualData = result.data.results || result.data;
                this.displayResults(actualData, queryString);
                this.addToHistory(queryString);
                this.stats.totalQueries++;
                this.updateStats();
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            console.error('Query execution error:', error);
            this.showError('Sorgu çalıştırılırken bir hata oluştu');
        } finally {
            this.setLoading(false);
        }
    }

    displayResults(data, query) {
        const resultPanel = document.getElementById('result');
        const resultBody = resultPanel?.querySelector('.panel-body');

        if (!resultPanel || !resultBody) return;

        resultPanel.className = 'result-panel panel panel-success';

        let html = `
            <div class="query-info">
                <h4>✅ Çalıştırılan Sorgu</h4>
                <pre><code class="sql">${this.escapeHtml(query)}</code></pre>
            </div>
        `;

        if (!data || data.length === 0) {
            html += `
                <div class="no-results">
                    <div class="empty-icon">📭</div>
                    <h4>Sonuç Bulunamadı</h4>
                    <p>Bu sorgu herhangi bir veri döndürmedi.</p>
                </div>
            `;
        } else {
            html += this.buildResultTable(data);
        }

        resultBody.innerHTML = html;

        const exportBtn = document.getElementById('exportResults');
        if (exportBtn) {
            exportBtn.disabled = false;
            exportBtn.setAttribute('data-results', JSON.stringify(data));
        }

        if (typeof hljs !== 'undefined') {
            resultBody.querySelectorAll('pre code').forEach(block => {
                hljs.highlightBlock(block);
            });
        }

        resultPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    buildResultTable(data) {
        const columnNames = Object.keys(data[0] || {});

        let html = `
            <div class="result-summary">
                <h4>📊 Sorgu Sonucu</h4>
                <p><strong>${data.length}</strong> kayıt bulundu</p>
            </div>
            <div class="table-container">
                <table class='result-table'>
                    <thead>
                        <tr>
        `;

        columnNames.forEach(columnName => {
            html += `<th>${this.escapeHtml(columnName)}</th>`;
        });

        html += `
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.forEach((row, index) => {
            html += `<tr class="fade-in" style="animation-delay: ${index * 50}ms">`;
            columnNames.forEach(columnName => {
                const value = row[columnName] ?? '';
                html += `<td>${this.escapeHtml(String(value))}</td>`;
            });
            html += '</tr>';
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        return html;
    }

    formatSQL() {
        const editor = document.getElementById('txtSqlQuery');
        if (!editor) return;

        let sql = editor.value;
        if (!sql.trim()) return;

        sql = sql
            .replace(/\bSELECT\b/gi, 'SELECT')
            .replace(/\bFROM\b/gi, '\nFROM')
            .replace(/\bWHERE\b/gi, '\nWHERE')
            .replace(/\bORDER BY\b/gi, '\nORDER BY')
            .replace(/\bGROUP BY\b/gi, '\nGROUP BY')
            .replace(/\bHAVING\b/gi, '\nHAVING')
            .replace(/\bLIMIT\b/gi, '\nLIMIT')
            .replace(/\bJOIN\b/gi, '\nJOIN')
            .replace(/\bINNER JOIN\b/gi, '\nINNER JOIN')
            .replace(/\bLEFT JOIN\b/gi, '\nLEFT JOIN')
            .replace(/\bRIGHT JOIN\b/gi, '\nRIGHT JOIN')
            .replace(/\bUNION\b/gi, '\nUNION');

        editor.value = sql;
        this.updateQueryStats();
    }

    clearEditor() {
        const editor = document.getElementById('txtSqlQuery');
        if (editor) {
            editor.value = '';
            editor.focus();
            this.updateQueryStats();
        }
    }

    clearAll() {
        this.clearEditor();
        this.clearResults();

        document.querySelectorAll('.table-item').forEach(item => {
            item.classList.remove('active');
        });
    }

    clearResults() {
        const resultPanel = document.getElementById('result');
        const resultBody = resultPanel?.querySelector('.panel-body');

        if (resultPanel && resultBody) {
            resultPanel.className = 'result-panel panel panel-default';
            resultBody.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">🚀</div>
                    <h4>Modern SQL Editor'e Hoş Geldiniz!</h4>
                    <p>Başlamak için sol panelden bir tablo seçin veya yukarıdaki editöre SQL sorgunuzu yazın.</p>
                </div>
            `;
        }

        const exportBtn = document.getElementById('exportResults');
        if (exportBtn) {
            exportBtn.disabled = true;
        }
    }

    exportResults() {
        const exportBtn = document.getElementById('exportResults');
        if (!exportBtn || exportBtn.disabled) return;

        const data = JSON.parse(exportBtn.getAttribute('data-results') || '[]');
        if (data.length === 0) return;

        const csv = this.convertToCSV(data);
        this.downloadFile(csv, 'sql_results.csv', 'text/csv');
    }

    convertToCSV(data) {
        if (data.length === 0) return '';

        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => 
                headers.map(header => 
                    `"${String(row[header] || '').replace(/"/g, '""')}"`
                ).join(',')
            )
        ].join('\n');

        return csvContent;
    }

    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    addToHistory(query) {
        const timestamp = new Date().toISOString();
        this.queryHistory.unshift({ query, timestamp });

        if (this.queryHistory.length > 50) {
            this.queryHistory = this.queryHistory.slice(0, 50);
        }

        this.saveQueryHistory();
    }

    saveQueryHistory() {
        try {
            localStorage.setItem('sqlEditor_history', JSON.stringify(this.queryHistory));
        } catch (error) {
            console.warn('Query history kaydetme hatası:', error);
        }
    }

    loadQueryHistory() {
        try {
            const saved = localStorage.getItem('sqlEditor_history');
            if (saved) {
                this.queryHistory = JSON.parse(saved);
            }
        } catch (error) {
            console.warn('Query history yükleme hatası:', error);
            this.queryHistory = [];
        }
    }

    setupAutoResize(textarea) {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.max(150, textarea.scrollHeight) + 'px';
        };

        textarea.addEventListener('input', resize);
        resize();
    }

    updateQueryStats() {
        const editor = document.getElementById('txtSqlQuery');
        const statsElement = document.getElementById('queryLength');

        if (editor && statsElement) {
            const length = editor.value.length;
            statsElement.textContent = length;
        }
    }

    updateStats() {
        const totalQueries = document.getElementById('totalQueries');
        const totalTables = document.getElementById('totalTables');

        if (totalQueries) {
            totalQueries.textContent = this.formatNumber(this.stats.totalQueries);
        }

        if (totalTables) {
            totalTables.textContent = this.formatNumber(this.stats.totalTables);
        }
    }

    setLoading(loading) {
        this.isLoading = loading;
        const runButton = document.getElementById('btnRun');
        const spinner = runButton?.querySelector('.loading-spinner');
        const icon = runButton?.querySelector('.btn-icon');

        if (runButton) {
            if (loading) {
                runButton.classList.add('loading');
                runButton.disabled = true;
                if (spinner) spinner.classList.remove('hidden');
                if (icon) icon.style.display = 'none';
            } else {
                runButton.classList.remove('loading');
                runButton.disabled = false;
                if (spinner) spinner.classList.add('hidden');
                if (icon) icon.style.display = 'inline';
            }
        }
    }

    showError(message) {
        const resultPanel = document.getElementById('result');
        const resultBody = resultPanel?.querySelector('.panel-body');

        if (!resultPanel || !resultBody) return;

        resultPanel.className = 'result-panel panel panel-danger';
        resultBody.innerHTML = `
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h4>Sorgu Hatası</h4>
                <p><strong>${this.escapeHtml(message)}</strong></p>
                <div class="error-actions">
                    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('txtSqlQuery').focus()">
                        Sorguyu Düzenle
                    </button>
                </div>
            </div>
        `;
    }

    showDatabaseGenerator() {
        this.showModal('randomDatabaseGeneratorModal');

        const iframe = document.getElementById('page');
        if (iframe) {
            iframe.src = './tools/generator.php';
            iframe.onload = () => {
                this.updateProgressBar();
                this.enableButton('btnSuccessfully');
            };
        }
    }

    showDatabaseReset() {
        this.showModal('resetDatabaseModal');

        const iframe = document.getElementById('resetDatabasePage');
        if (iframe) {
            iframe.src = './tools/reset-database.php';
            iframe.onload = () => {
                this.enableButton('btnResetSuccessfully');
            };
        }
    }

    updateProgressBar() {
        const progressBar = document.querySelector('[role=progressbar]');
        if (progressBar) {
            progressBar.classList.add('progress-bar-success');
            progressBar.classList.remove('progress-bar-striped', 'active');
        }
    }

    enableButton(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.removeAttribute('disabled');
        }
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && typeof $ !== 'undefined') {
            $(modal).modal({
                backdrop: 'static',
                keyboard: false
            }).modal('show');
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal && typeof $ !== 'undefined') {
            $(modal).modal('hide');
        }
    }

    showExamples() {
        const examples = [
            { name: '👥 Müşteri Listesi', query: 'SELECT * FROM Musteriler LIMIT 10;' },
            { name: '🛍️ Pahalı Ürünler', query: 'SELECT * FROM Urunler WHERE Fiyat > 100 ORDER BY Fiyat DESC LIMIT 5;' },
            { name: '📊 Sipariş İstatistikleri', query: 'SELECT COUNT(*) as ToplamSiparis, AVG(Adet) as OrtalamaMiktar FROM SiparisDetaylari;' },
            { name: '🏪 Satıcı Ürün Sayıları', query: 'SELECT s.SaticiAdi, COUNT(u.UrunID) as UrunSayisi FROM Saticilar s LEFT JOIN Urunler u ON s.SaticiID = u.SaticiID GROUP BY s.SaticiID;' }
        ];

        let html = '<div class="examples-grid">';
        examples.forEach(example => {
            html += `
                <button class="btn btn-outline-primary example-item" data-query="${this.escapeHtml(example.query)}">
                    ${example.name}
                </button>
            `;
        });
        html += '</div>';

        this.showCustomModal('Örnek Sorgular', html);
    }

    showHelp() {
        const helpContent = `
            <div class="help-content">
                <h5>🔧 Kısayollar</h5>
                <ul>
                    <li><strong>Ctrl + Enter:</strong> Sorguyu çalıştır</li>
                    <li><strong>F11:</strong> Tam ekran modu</li>
                </ul>

                <h5>📊 SQL İpuçları</h5>
                <ul>
                    <li><strong>LIMIT:</strong> Sonuçları sınırla (örn: LIMIT 10)</li>
                    <li><strong>ORDER BY:</strong> Sonuçları sırala</li>
                    <li><strong>WHERE:</strong> Koşul belirt</li>
                    <li><strong>COUNT(*):</strong> Kayıt sayısını getir</li>
                </ul>

                <h5>🎨 Özellikler</h5>
                <ul>
                    <li>🌙 Dark mode desteği</li>
                    <li>📱 Mobil uyumlu</li>
                    <li>📤 Sonuçları CSV olarak dışa aktar</li>
                    <li>🔍 Tablo arama</li>
                </ul>
            </div>
        `;

        this.showCustomModal('Yardım', helpContent);
    }

    showCustomModal(title, content) {

        const modalHtml = `
            <div class="modal fade" id="customModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content modern-modal">
                        <div class="modal-header">
                            <h4 class="modal-title">${title}</h4>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const existingModal = document.getElementById('customModal');
        if (existingModal) {
            existingModal.remove();
        }

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        if (typeof $ !== 'undefined') {
            $('#customModal').modal('show');

            $('#customModal .example-item').on('click', (e) => {
                const query = e.target.getAttribute('data-query');
                this.setQuery(query);
                $('#customModal').modal('hide');
            });
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('tr-TR').format(num);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    showAlert(message, type = 'info', duration = 5000) {
        console.log(`${type.toUpperCase()}: ${message}`);

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-floating`;
        alertDiv.innerHTML = `
            <span class="alert-icon">${this.getAlertIcon(type)}</span>
            <span class="alert-message">${message}</span>
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span>&times;</span>
            </button>
        `;

        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, duration);
    }

    getAlertIcon(type) {
        const icons = {
            'success': '✅',
            'info': 'ℹ️',
            'warning': '⚠️',
            'danger': '❌',
            'error': '❌'
        };
        return icons[type] || 'ℹ️';
    }

    setupQuestionAnalysis() {
        const questionTextarea = document.getElementById('txtQuestion');
        const analyzeButton = document.getElementById('btnAnalyze');
        const clearQuestionButton = document.getElementById('clearQuestion');
        const questionLengthSpan = document.getElementById('questionLength');
        const questionExampleButtons = document.querySelectorAll('.question-example-btn');

        if (questionTextarea) {

            questionTextarea.addEventListener('input', () => {
                if (questionLengthSpan) {
                    questionLengthSpan.textContent = questionTextarea.value.length;
                }
            });

            questionTextarea.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.analyzeQuestion();
                }
            });
        }

        if (analyzeButton) {
            analyzeButton.addEventListener('click', () => {
                this.analyzeQuestion();
            });
        }

        if (clearQuestionButton) {
            clearQuestionButton.addEventListener('click', () => {
                this.clearQuestion();
            });
        }

        questionExampleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const question = button.getAttribute('data-question');
                if (questionTextarea && question) {
                    questionTextarea.value = question;
                    if (questionLengthSpan) {
                        questionLengthSpan.textContent = question.length;
                    }
                }
            });
        });
    }

    async analyzeQuestion() {
        const questionTextarea = document.getElementById('txtQuestion');
        const analyzeButton = document.getElementById('btnAnalyze');
        const questionResult = document.getElementById('questionResult');

        if (!questionTextarea || !questionTextarea.value.trim()) {
            this.showAlert('Lütfen bir soru yazın.', 'warning');
            return;
        }

        const question = questionTextarea.value.trim();

        this.setQuestionAnalysisLoading(true);

        try {

            const result = await this.analyzeQuestionWithOllama(question);
            this.displayQuestionResult(result);

        } catch (error) {
            console.error('Question analysis error:', error);
            this.showAlert('Soru analizi sırasında hata oluştu: ' + error.message, 'danger');
        } finally {
            this.setQuestionAnalysisLoading(false);
        }
    }

    setQuestionAnalysisLoading(isLoading) {
        const analyzeButton = document.getElementById('btnAnalyze');
        const spinner = analyzeButton?.querySelector('.loading-spinner');
        const icon = analyzeButton?.querySelector('.btn-icon');

        if (analyzeButton) {
            analyzeButton.disabled = isLoading;
        }

        if (spinner) {
            spinner.classList.toggle('hidden', !isLoading);
        }

        if (icon) {
            icon.style.display = isLoading ? 'none' : 'inline';
        }
    }

    displayQuestionResult(result) {
        const questionResult = document.getElementById('questionResult');
        if (!questionResult) return;

        const panelBody = questionResult.querySelector('.panel-body');
        if (!panelBody) return;

        let html = `
            <div class="question-results show">
                <div class="analysis-summary">
                    <h4>🎯 Analiz Sonucu ${result.powered_by ? `(${result.powered_by})` : ''}</h4>
                    <p>Sorunuz için önerilen tablo ve kolonlar:</p>
                </div>
        `;

        if (result.ai_response) {
            html += `
                <div class="ai-explanation">
                    <h5>🤖 AI Açıklaması:</h5>
                    <div class="ai-response-text">${result.ai_response.replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }

        html += `<div class="suggested-tables">`;

        result.suggestions.forEach(suggestion => {
            html += `
                <div class="table-suggestion">
                    <div class="table-name">
                        <span>${suggestion.icon}</span>
                        ${suggestion.table}
                    </div>
                    <div class="table-description">
                        ${suggestion.description}
                    </div>
                    <div class="suggested-columns">
            `;

            suggestion.columns.forEach(column => {
                const isPrimary = suggestion.primary_columns?.includes(column);
                html += `<span class="column-tag ${isPrimary ? 'primary' : ''}">${column}</span>`;
            });

            html += `
                    </div>
                </div>
            `;
        });

        if (result.suggested_query) {
            html += `
                <div class="suggested-query">
                    <h5>💡 Önerilen SQL Sorgusu</h5>
                    <div class="query-code">${this.escapeHtml(result.suggested_query)}</div>
                    <button class="btn btn-outline-success btn-sm copy-query-btn" data-query="${this.escapeHtml(result.suggested_query)}">
                        📋 Sorguyu Editöre Kopyala
                    </button>
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        panelBody.innerHTML = html;

        const copyBtn = panelBody.querySelector('.copy-query-btn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const query = copyBtn.getAttribute('data-query');
                this.copyQueryToEditor(query);
            });
        }
    }

    copyQueryToEditor(query) {
        const sqlEditor = document.getElementById('txtSqlQuery');
        if (sqlEditor) {
            sqlEditor.value = query.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
            sqlEditor.focus();
            this.showAlert('✅ Sorgu editöre kopyalandı!', 'success', 3000);

            const queryLength = document.getElementById('queryLength');
            if (queryLength) {
                queryLength.textContent = sqlEditor.value.length;
            }
        }
    }

    clearQuestion() {
        const questionTextarea = document.getElementById('txtQuestion');
        const questionLengthSpan = document.getElementById('questionLength');
        const questionResult = document.getElementById('questionResult');

        if (questionTextarea) {
            questionTextarea.value = '';
        }

        if (questionLengthSpan) {
            questionLengthSpan.textContent = '0';
        }

        if (questionResult) {
            const panelBody = questionResult.querySelector('.panel-body');
            if (panelBody) {
                panelBody.innerHTML = `
                    <div class="question-welcome-message">
                        <div class="welcome-icon">🤔</div>
                        <h4>Soru Analiz Sistemi</h4>
                        <p>Yukarıdaki kutuya sorunuzu yazın, hangi tablo ve kolonları kullanmanız gerektiğini öğrenin.</p>
                        <div class="quick-questions">
                            <h5>Örnek Sorular:</h5>
                            <div class="example-buttons">
                                <button class="btn btn-outline-info btn-sm question-example-btn" data-question="En çok satılan ürünleri nasıl bulabilirim?">
                                    🛍️ En çok satılan ürünler
                                </button>
                                <button class="btn btn-outline-info btn-sm question-example-btn" data-question="Hangi müşteriler en çok sipariş veriyor?">
                                    👥 En aktif müşteriler
                                </button>
                                <button class="btn btn-outline-info btn-sm question-example-btn" data-question="Aylık satış raporunu nasıl çıkarabilirim?">
                                    📈 Aylık satış raporu
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                this.setupQuestionAnalysis();
            }
        }
    }

    async analyzeQuestionSimple(question) {
        const response = await fetch('simple-question-analyzer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                question: question
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    async analyzeQuestionWithOllama(question) {
        const response = await fetch('ollama-question-analyzer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                question: question
            })
        });

        if (!response.ok) {

            console.warn('Ollama başarısız, pattern matching kullanılıyor');
            return await this.analyzeQuestionSimple(question);
        }

        const result = await response.json();

        if (result.fallback) {

            console.warn('Ollama fallback istedi:', result.error);
            return await this.analyzeQuestionSimple(question);
        }

        return result;
    }
}

let app;

document.addEventListener('DOMContentLoaded', () => {
    app = new ModernSQLEditor();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        app = new ModernSQLEditor();
    });
} else {
    app = new ModernSQLEditor();
}