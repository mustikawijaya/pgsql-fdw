# Contributing to Laravel Postgres FDW

Thank you for your interest in contributing to **Laravel Postgres FDW** (`mustikawijaya/pgsql-fdw`)! We welcome contributions from the community to help improve this package.

To ensure a smooth, efficient, and consistent collaboration process, please read and follow these guidelines.

---

## Code of Conduct

By participating in this project, you agree to maintain a respectful, inclusive, and professional environment for all contributors and maintainers. Harassment or disrespectful behavior of any kind will not be tolerated.

---

## How Can You Contribute?

### 1. Reporting Bugs

If you find a bug or unexpected behavior, please submit an issue on the [GitHub Issues](https://github.com/mustikawijaya/pgsql-fdw/issues) page. Before creating a new issue:

1. **Search existing issues** to verify that the bug has not already been reported or resolved.
2. **Provide a clear and descriptive title**.
3. **Include reproducible details**:
   - Exact steps to reproduce the issue.
   - PHP and Laravel framework versions.
   - PostgreSQL version.
   - Relevant database configuration or error stack traces (ensure sensitive credentials are redacted).
   - Expected behavior vs. actual behavior.

---

### 2. Suggesting Enhancements & Features

We welcome ideas for new features and improvements. To propose an enhancement:

1. Open a new issue and label it as an **Enhancement / Feature Request**.
2. Explain the motivation: what problem does this feature solve?
3. Provide a clear description or code snippet illustrating how the feature would be used.
4. Allow time for maintainers to discuss the proposal before opening a Pull Request.

---

### 3. Submitting Pull Requests (Code Contributions)

Please adhere to the following workflow when contributing code:

#### Step 1: Fork and Clone
1. Fork the repository to your GitHub account.
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/pgsql-fdw.git
   cd pgsql-fdw
   ```
3. Install development dependencies:
   ```bash
   composer install
   ```

#### Step 2: Create a Dedicated Branch
Always branch off the latest `master` branch. Use a clear and descriptive branch prefix:
- **`feature/your-feature-name`** for new features.
- **`fix/your-bug-fix-name`** for bug fixes.
- **`docs/your-doc-update`** for documentation changes.

```bash
git checkout master
git pull origin master
git checkout -b feature/support-custom-types
```

#### Step 3: Implement Changes & Coding Standards
- **Adhere to PSR-12 coding standards**: Keep code clean, readable, and consistent with the existing codebase.
- **Strict Data Type Handling**: When touching database inspection or DDL generation, ensure data type precision, scale, and nullability are preserved.
- **Write Clear Commit Messages**: Use clear, conventional commit messages:
  - `feat: add support for PostgreSQL enum inspection`
  - `fix: resolve drop cascade issue on foreign tables`
  - `docs: update installation instructions in README`

#### Step 4: Add and Run Tests
Every new feature or bug fix **must include tests**:
- Unit tests in `tests/Unit/` for isolated service logic.
- Integration tests in `tests/Integration/` if touching PostgreSQL FDW DDL execution.

Run the test suite before submitting:
```bash
./vendor/bin/phpunit
```
**All tests must pass (100% green) before a Pull Request will be considered.**

#### Step 5: Submit the Pull Request
1. Push your branch to your forked repository:
   ```bash
   git push -u origin feature/your-feature-name
   ```
2. Open a **Pull Request (PR)** against the `master` branch of `mustikawijaya/pgsql-fdw`.
3. Fill out the PR description:
   - Reference any related issues (e.g., `Fixes #12`).
   - Describe what was changed and why.
   - Confirm that all tests pass.

---

## Review Process

- Maintainers will review your Pull Request as soon as possible.
- Feedback or requests for revisions may be provided in the PR conversation.
- Once approved and continuous integration checks pass, a maintainer will merge your Pull Request into `master`.

---

## License

By contributing to this project, you agree that your contributions will be licensed under the project's [MIT License](LICENSE).
