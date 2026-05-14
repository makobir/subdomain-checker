<?php
/**
 * Pure API-based Subdomain Discovery Tool
 * Uses only HackerTarget API - no hardcoded data
 */

// Disable error output to keep JSON clean
error_reporting(0);
ini_set('display_errors', 0);

class SubdomainAPIChecker {
    private $domain;
    private $apiUrl = "https://api.hackertarget.com/hostsearch/";
    
    public function __construct($domain) {
        $this->domain = strtolower(trim($domain));
    }
    
    /**
     * Fetch subdomains from HackerTarget API
     */
    public function fetchFromAPI() {
        $url = $this->apiUrl . "?q=" . urlencode($this->domain);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SubdomainChecker/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            return ['error' => "CURL Error: $curlError"];
        }
        
        if ($httpCode !== 200) {
            return ['error' => "API returned HTTP $httpCode"];
        }
        
        if (!$response) {
            return ['error' => "Empty response from API"];
        }
        
        return $this->parseResponse($response);
    }
    
    /**
     * Parse API response
     */
    private function parseResponse($response) {
        $subdomains = [];
        $lines = explode("\n", trim($response));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, ',') !== false) {
                list($subdomain, $ip) = explode(',', $line, 2);
                $subdomain = trim($subdomain);
                $ip = trim($ip);
                
                // Only include subdomains that belong to the target domain
                if (strpos($subdomain, $this->domain) !== false) {
                    $subdomains[] = [
                        'subdomain' => $subdomain,
                        'ip' => $ip
                    ];
                }
            }
        }
        
        return $subdomains;
    }
}

// Handle different request types
if (php_sapi_name() === 'cli') {
    // Command line mode
    if ($argc < 2) {
        echo "\n🔍 Subdomain Discovery Tool (HackerTarget API)\n";
        echo "Usage: php " . $argv[0] . " <domain>\n";
        echo "Example: php " . $argv[0] . " vai.bd\n";
        echo "Example: php " . $argv[0] . " google.com\n\n";
        exit(1);
    }
    
    $domain = $argv[1];
    $checker = new SubdomainAPIChecker($domain);
    $result = $checker->fetchFromAPI();
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "🔍 SUBDOMAIN DISCOVERY RESULTS\n";
    echo "Target: $domain\n";
    echo "Source: HackerTarget API\n";
    echo str_repeat("=", 70) . "\n\n";
    
    if (isset($result['error'])) {
        echo "❌ Error: " . $result['error'] . "\n\n";
        exit(1);
    }
    
    if (count($result) === 0) {
        echo "⚠ No subdomains found for $domain\n\n";
        echo "Possible reasons:\n";
        echo "  • Domain has no subdomains\n";
        echo "  • API daily limit reached (20 queries/day)\n";
        echo "  • No data available for this domain\n\n";
        exit(0);
    }
    
    echo "✓ Found " . count($result) . " subdomain(s):\n\n";
    echo str_repeat("-", 70) . "\n";
    
    foreach ($result as $item) {
        echo sprintf("  %-45s → %s\n", $item['subdomain'], $item['ip']);
    }
    
    echo str_repeat("-", 70) . "\n\n";
    
    // Save to file
    $filename = "subdomains_{$domain}_" . date('Y-m-d_H-i-s') . ".txt";
    $fileContent = "";
    foreach ($result as $item) {
        $fileContent .= $item['subdomain'] . "," . $item['ip'] . "\n";
    }
    file_put_contents($filename, $fileContent);
    echo "💾 Results saved to: $filename\n\n";
    
} else {
    // Web mode - AJAX handler
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        $domain = $_POST['domain'] ?? $_GET['domain'] ?? null;
        
        if (!$domain) {
            echo json_encode(['success' => false, 'error' => 'Domain parameter required']);
            exit;
        }
        
        // Validate domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,}$/', $domain)) {
            echo json_encode(['success' => false, 'error' => 'Invalid domain format']);
            exit;
        }
        
        $checker = new SubdomainAPIChecker($domain);
        $result = $checker->fetchFromAPI();
        
        if (isset($result['error'])) {
            echo json_encode(['success' => false, 'error' => $result['error']]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'domain' => $domain,
            'total' => count($result),
            'subdomains' => $result,
            'source' => 'HackerTarget API'
        ]);
        exit;
    }
    
    // Serve HTML interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Subdomain Discovery Tool | HackerTarget API</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                overflow: hidden;
            }
            
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 40px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 2.5em;
                margin-bottom: 10px;
            }
            
            .header p {
                opacity: 0.9;
                font-size: 1.1em;
            }
            
            .content {
                padding: 40px;
            }
            
            .input-group {
                margin-bottom: 25px;
            }
            
            label {
                display: block;
                margin-bottom: 10px;
                font-weight: 600;
                color: #333;
                font-size: 1.1em;
            }
            
            .input-wrapper {
                display: flex;
                gap: 10px;
            }
            
            input[type="text"] {
                flex: 1;
                padding: 14px 18px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                font-size: 16px;
                font-family: monospace;
                transition: all 0.3s;
            }
            
            input[type="text"]:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            }
            
            button {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 14px 32px;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            .results {
                margin-top: 30px;
                display: none;
            }
            
            .results.active {
                display: block;
                animation: fadeIn 0.5s;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .stats {
                background: #f0f4f8;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-around;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                min-width: 150px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .stat-number {
                font-size: 2em;
                font-weight: bold;
                color: #667eea;
            }
            
            .stat-label {
                color: #666;
                margin-top: 5px;
                font-size: 0.9em;
            }
            
            .table-container {
                max-height: 500px;
                overflow-y: auto;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            th {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                text-align: left;
                position: sticky;
                top: 0;
                font-weight: 600;
            }
            
            td {
                padding: 12px 15px;
                border-bottom: 1px solid #e0e0e0;
                font-family: monospace;
            }
            
            tr:hover {
                background: #f8f9fa;
            }
            
            .loading {
                text-align: center;
                padding: 50px;
                display: none;
            }
            
            .loading.active {
                display: block;
            }
            
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #667eea;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .error {
                background: #fee;
                color: #c33;
                padding: 15px;
                border-radius: 12px;
                margin-top: 20px;
                border-left: 4px solid #c33;
                display: none;
            }
            
            .error.active {
                display: block;
            }
            
            .info {
                background: #e3f2fd;
                padding: 15px;
                border-radius: 12px;
                margin-top: 20px;
                font-size: 14px;
                border-left: 4px solid #2196f3;
            }
            
            .api-badge {
                background: #4caf50;
                color: white;
                padding: 3px 10px;
                border-radius: 20px;
                font-size: 12px;
                display: inline-block;
                margin-left: 10px;
            }
            
            @media (max-width: 768px) {
                .content {
                    padding: 20px;
                }
                
                .input-wrapper {
                    flex-direction: column;
                }
                
                .stats {
                    flex-direction: column;
                }
                
                th, td {
                    padding: 8px;
                    font-size: 12px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="header">
                    <h1>🔍 Subdomain Discovery Tool</h1>
                    <p>Powered by HackerTarget API <span class="api-badge">Live API</span></p>
                </div>
                <div class="content">
                    <form id="searchForm">
                        <div class="input-group">
                            <label for="domain">🌐 Enter Domain Name:</label>
                            <div class="input-wrapper">
                                <input type="text" 
                                       id="domain" 
                                       name="domain" 
                                       placeholder="e.g., vai.bd, google.com" 
                                       required 
                                       autocomplete="off">
                                <button type="submit">🔎 Discover Subdomains</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Querying HackerTarget API...</p>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">Free API: 20 queries/day, 50 results/request</p>
                    </div>
                    
                    <div class="error" id="error"></div>
                    
                    <div class="results" id="results">
                        <div class="stats" id="stats"></div>
                        <h3>📋 Discovered Subdomains:</h3>
                        <div class="table-container">
                            <table id="subdomainTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Subdomain</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: #999;">
                                            Enter a domain and click search
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="info">
                        💡 <strong>About this tool:</strong> This tool uses the live HackerTarget API 
                        (https://api.hackertarget.com/hostsearch/) to find subdomains. No data is hardcoded.
                        Free tier allows 20 queries per day with 50 results per request.
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            const form = document.getElementById('searchForm');
            const domainInput = document.getElementById('domain');
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            const errorDiv = document.getElementById('error');
            const stats = document.getElementById('stats');
            const tableBody = document.getElementById('tableBody');
            
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const domain = domainInput.value.trim().toLowerCase();
                if (!domain) {
                    showError('Please enter a domain name');
                    return;
                }
                
                // Validate domain format
                const domainRegex = /^[a-z0-9][a-z0-9\.\-]+\.[a-z]{2,}$/;
                if (!domainRegex.test(domain)) {
                    showError('Please enter a valid domain (e.g., example.com)');
                    return;
                }
                
                // Show loading
                loading.classList.add('active');
                results.classList.remove('active');
                errorDiv.classList.remove('active');
                
                try {
                    const formData = new FormData();
                    formData.append('domain', domain);
                    
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    loading.classList.remove('active');
                    
                    if (!data.success) {
                        showError(data.error || 'Failed to fetch subdomains');
                        return;
                    }
                    
                    if (data.total === 0) {
                        showError(`No subdomains found for ${domain}. Try another domain like "google.com"`);
                        return;
                    }
                    
                    // Display stats
                    stats.innerHTML = `
                        <div class="stat-card">
                            <div class="stat-number">${data.total}</div>
                            <div class="stat-label">Total Subdomains</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">${domain}</div>
                            <div class="stat-label">Target Domain</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">API</div>
                            <div class="stat-label">Data Source</div>
                        </div>
                    `;
                    
                    // Display table
                    tableBody.innerHTML = '';
                    data.subdomains.forEach((item, index) => {
                        const row = tableBody.insertRow();
                        row.insertCell(0).textContent = index + 1;
                        row.insertCell(1).textContent = item.subdomain;
                        row.insertCell(2).textContent = item.ip;
                    });
                    
                    results.classList.add('active');
                    
                } catch (error) {
                    loading.classList.remove('active');
                    showError('Network error: ' + error.message);
                }
            });
            
            function showError(message) {
                errorDiv.textContent = '❌ ' + message;
                errorDiv.classList.add('active');
                setTimeout(() => {
                    errorDiv.classList.remove('active');
                }, 5000);
            }
        </script>
    </body>
    </html>
    <?php
}
?>