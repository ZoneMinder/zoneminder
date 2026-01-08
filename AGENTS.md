# Development Guidelines

## Quick Reference
1. **Feature Workflow**: New features → Create GH issue → Feature branch → Implement fully → Get approval → Merge to master
2. **Internationalization**: Update ALL language files (en, de, es, fr, zh + any future)
3. **Testing**: MANDATORY - Write tests first, run AND verify pass before commit
4. **Coding**: DRY principles, keep code files small and modular

---

## Testing (MANDATORY - No Exceptions)

### Test-First Development Workflow

**Rule**: Write tests BEFORE or DURING implementation, NEVER skip tests.

**Why**: Tests written "later" are usually never written. Tests verify code actually works.

**Workflow**:
1. Understand the bug/feature requirement
2. Write a failing test that reproduces the issue or validates the feature
3. Implement the fix/feature
4. Run tests - verify they now PASS
5. Run full test suite to check for regressions
6. Only then commit

### Unit Tests (REQUIRED - No Exceptions)

**When**: For ALL code changes - no matter how small
- ✅ New functionality → Write new tests FIRST
- ✅ Bug fixes → Write test that reproduces bug FIRST
- ✅ Refactoring → Ensure existing tests still pass
- ✅ Changes to existing functionality → Update tests BEFORE changing code
- ✅ New components → Write tests as you build
- ✅ Store changes → Update store tests
- ✅ Utility functions → Test all logic paths

**What to Test**:
- Happy path (normal usage)
- Edge cases (empty arrays, null values, undefined, boundary conditions)
- Error cases (network failures, invalid input, missing data)
- State changes (verify before/after behavior)

**Location**: Next to source in `tests/` subdirectory

## Feature Development Workflow (MANDATORY)

**When the user requests a new feature, follow this workflow:**

### 1. Create GitHub Issue
- Create a GitHub issue for the feature request using `gh issue create`
- Label it as `enhancement`
- Include clear description of what the feature should do
- Example:
  ```bash
  gh issue create --title "Add event favorites feature" \
    --body "Allow users to mark events as favorites and filter by favorites" \
    --label "enhancement"
  ```

### 2. Create Feature Branch
- Create a new branch from master with descriptive name
- Branch naming: `feature/<short-description>` or `feat/<issue-number>-<description>`
- Example:
  ```bash
  git checkout -b feature/event-favorites
  ```

### 3. Implement Feature Completely
- **CRITICAL:** Implement the ENTIRE feature - do not stop in the middle
- Follow all testing requirements (unit tests, E2E tests, type check, build)
- Commit work in logical chunks with descriptive messages
- Reference the issue in commit messages: `refs #<issue-number>`
- Make multiple commits if the feature has multiple logical components

### 4. Request User Feedback
- Once implementation is complete and all tests pass, ask user for feedback
- DO NOT merge or push without user approval
- Example: "Feature implementation complete. All tests passing. Ready for your review."

### 5. Merge and Cleanup (After User Approval Only)
- Merge feature branch to master
- Delete the feature branch (local and remote)
- Reference the issue in final commit/merge: `fixes #<issue-number>`
- Push to master
- Verify issue is automatically closed (due to `fixes #<number>`)

**Example Complete Workflow:**
```bash
# 1. Create issue
gh issue create --title "Add dark mode toggle" --body "..." --label "enhancement"
# Note the issue number (e.g., #42)

# 2. Create branch
git checkout -b feature/dark-mode

# 3. Implement + test + commit
git add <files>
git commit -m "feat: add dark mode toggle component refs #42"
# ... more commits as needed

# 4. Ask user for approval
# (Wait for user confirmation)

# 5. After approval, merge and cleanup
git checkout master
git merge feature/dark-mode
git push origin master
git branch -d feature/dark-mode
git push origin --delete feature/dark-mode
# Verify issue #42 is closed
```

**Important Notes:**
- Never merge to master without user approval
- Never leave a feature half-implemented
- Always include tests before requesting approval
- Feature branches keep master stable and allow for review

---

## Commits

- Commit messages must be detailed and descriptive (no vague summaries)
- Split unrelated changes into separate commits (one logical change per commit)
- Avoid superlative language (no "comprehensive", "critical", "major", "massive", etc.)
- Keep commit messages factual and objective
- **Use conventional commit format:**
    - `feat:` - New feature
    - `fix:` - Bug fix
    - `docs:` - Documentation
    - `test:` - Tests
    - `chore:` - Maintenance
    - `refactor:` - Code restructuring
- When you commit code, and the code contains multiple things, break each item into separate commits

**Examples:**
- ✅ Good: `fix: resolve overflow issue in flex containers`
- ✅ Good: `feat: add haptic feedback to buttons`
- ❌ Bad: `fix: comprehensive overflow handling improvements`
- ❌ Bad: `feat: critical haptic feedback system`




## Issue handling
- When Github issues are created, make sure code fixes refer to that issue in commit messages
- Use `refs #<id>` for references and `fixes #<id>` when the commit should close the issue
- When working in github issues, make changes, validate tests and then ask me to test before pushing code to github

---

## Pre-Commit Checklist

### ALL Changes (MANDATORY - No Exceptions)
- [ ] Tests written/updated BEFORE or DURING implementation

### Before Stating "Done" or Committing
- [ ] ALL applicable tests have been run (not just build)
- [ ] ALL tests PASS (not just "no errors")
- [ ] State which tests were run and passed

### Never Commit or Claim Complete If
- ❌ Tests are failing
- ❌ Tests don't exist for new/changed functionality
- ❌ You haven't actually run the tests
- ❌ Build fails
- ❌ You only ran build but not unit/e2e tests

