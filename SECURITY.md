# Security Policy

## Supported Versions

We release patches and security updates for the following versions of `mustikawijaya/pgsql-fdw`:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

---

## Reporting a Vulnerability

We take the security of `mustikawijaya/pgsql-fdw` seriously. If you discover a security vulnerability, please help us protect our users by disclosing it responsibly.

**Please do NOT report security vulnerabilities through public GitHub issues, pull requests, or public discussions.**

### How to Submit a Vulnerability Report

Please report security issues using **GitHub Private Vulnerability Reporting**:

1. Navigate to the repository page: [github.com/mustikawijaya/pgsql-fdw](https://github.com/mustikawijaya/pgsql-fdw).
2. Click on the **Security** tab located above the file list.
3. Under the "Reporting" section on the left sidebar, click **Advisories**.
4. Click the green **Report a vulnerability** button to open the private advisory draft.
5. Provide detailed information to help us understand and reproduce the vulnerability:
   - Type of issue (e.g., SQL injection, credential leakage, privilege escalation).
   - Step-by-step instructions or proof-of-concept (PoC) code to reproduce the issue.
   - Affected package versions, PHP versions, and PostgreSQL configurations.
   - Potential impact and recommended mitigation, if known.

---

## Response Process & Timeline

Once a vulnerability report is submitted:

1. **Investigation & Assessment:** We will verify the report, determine its severity, and keep you informed of our progress.
2. **Fix Development:** We will prepare and test a security patch in a private temporary repository / advisory workspace.
3. **Coordinated Disclosure:** Once the fix is verified, we will publish a new patch release and publish a security advisory with full credit to the reporter (unless you prefer to remain anonymous).

---

## Scope & Guidelines

### In Scope
- Vulnerabilities in DDL generation logic that could lead to SQL injection.
- Unintentional leakage of database credentials or connection secrets in exception messages, log channels, or console outputs.
- Flaws in foreign table schema isolation allowing unauthorized cross-schema access or table hijacking.

### Out of Scope
- Vulnerabilities requiring direct physical access or root/superuser access to the underlying PostgreSQL server or host operating system.
- Attacks relying on social engineering or phishing.
- Vulnerabilities in third-party dependencies (e.g., upstream Laravel framework or PHP core) which should be reported directly to those respective upstream maintainers.

Thank you for helping keep `mustikawijaya/pgsql-fdw` and its community safe!
