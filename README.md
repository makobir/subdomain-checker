# 🔍 Subdomain Discovery Tool

A powerful, API-based subdomain discovery tool that queries the HackerTarget API to find all subdomains associated with a given domain. Perfect for security researchers, penetration testers, and system administrators.

[![PHP Version](https://img.shields.io/badge/PHP-7.0+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![API](https://img.shields.io/badge/API-HackerTarget-orange.svg)](https://hackertarget.com)

## ✨ Features

- 🚀 **Real-time API queries** - No hardcoded data, always fresh results
- 💻 **Dual interface** - Works on command line AND web browser
- 📊 **Beautiful web UI** - Modern, responsive design with statistics
- 💾 **Auto-save results** - Automatically saves discoveries to text files
- 🔄 **JSON API support** - Can be used as an API endpoint
- 📋 **Copy to clipboard** - One-click copy all discovered subdomains
- 🎯 **Domain validation** - Ensures valid domain format before querying

## 📋 Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [Command Line](#command-line)
  - [Web Interface](#web-interface)
  - [As an API](#as-an-api)
- [Examples](#examples)
- [API Information](#api-information)
- [Limitations](#limitations)
- [Use Cases](#use-cases)
- [Contributing](#contributing)
- [License](#license)

## 🚀 Installation

### Requirements
- PHP 7.0 or higher
- PHP cURL extension enabled
- Web server (optional - for web interface)

### Quick Install

```bash
# Clone the repository
git clone https://github.com/yourusername/subdomain-discovery-tool.git
cd subdomain-discovery-tool

# Download the tool
curl -O https://raw.githubusercontent.com/yourusername/subdomain-discovery-tool/main/subdomain_checker.php

# Make it executable (Linux/Mac)
chmod +x subdomain_checker.php
