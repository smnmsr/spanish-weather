# Test Refactoring Checklist

---

## Action Items

### 1. Delete Template Tests

- [ ] **Delete** `tests/Feature/ExampleTest.php`
- [ ] **Delete** `tests/Unit/ExampleTest.php`
- [ ] **Run** `php artisan test` to verify suite still passes

### 2. Combine Duplicate StationToolTest Files

- [ ] **Extract content** from `tests/Browser/StationToolTest.php` (browser interactions)
- [ ] **Add** `Http::fake(AemetFixtures::httpFakeConfig())` to all tests
- [ ] **Merge all tests** into `tests/Browser/StationToolTest.php`
    - Keep browser interaction tests
    - Add Inertia page rendering tests with mocked data
    - Keep StationMapPageTest functionality if relevant
- [ ] **Remove** `->skipOnCi()` from all tests in merged file
- [ ] **Delete** `tests/Feature/StationToolTest.php`
- [ ] **Delete** `tests/Feature/StationMapPageTest.php`
- [ ] **Run** `php artisan test tests/Browser/StationToolTest.php` to verify

### 3. Remove Redundant Test File

- [ ] **Review** `tests/Browser/StationToolRefactorTest.php` content
- [ ] **Extract unique tests** (if any) and add to `tests/Browser/StationToolTest.php` with mocked data
- [ ] **Delete** `tests/Browser/StationToolRefactorTest.php`

### 4. Archive Unused Auth Tests

- [ ] **Create** directory `tests/Archived/Auth/`
- [ ] **Create** directory `tests/Archived/Settings/`
- [ ] **Move** all files from `tests/Feature/Auth/` → `tests/Archived/Auth/`
- [ ] **Move** all files from `tests/Feature/Settings/` → `tests/Archived/Settings/`
- [ ] **Move** `tests/Feature/DashboardTest.php` → `tests/Archived/DashboardTest.php`

### 5. Final Verification

- [ ] **Run** `php artisan test` - all tests pass
- [ ] **Run** `composer run precommit` - all checks pass
- [ ] **Commit** changes with message: "refactor: clean up test organization"

---

## Expected Test Count After Changes

**Before**: ~30 test files  
**After**: ~20 active test files + 11 archived

**Active Tests**:

- Browser: 10 files (was 12, merged StationTool tests)
- Feature: 5 files (was 10, removed examples + merged + moved auth/settings)
- Unit: 0 files (was 1)

---

## Files to Delete

1. `tests/Feature/ExampleTest.php`
2. `tests/Unit/ExampleTest.php`
3. `tests/Feature/StationToolTest.php` (content merged to Browser)
4. `tests/Feature/StationMapPageTest.php` (content merged to Browser)
5. `tests/Browser/StationToolRefactorTest.php`

## Files to Modify

1. `tests/Browser/StationToolTest.php`
    - Add `Http::fake(AemetFixtures::httpFakeConfig())` to all tests
    - Add Inertia page rendering tests (from Feature/StationToolTest.php)
    - Add map page tests (from Feature/StationMapPageTest.php)
    - Remove all `->skipOnCi()` markers

## Files to Move (11 total)

**Auth Tests** (7 files):

1. `tests/Feature/Auth/AuthenticationTest.php` → `tests/Archived/Auth/AuthenticationTest.php`
2. `tests/Feature/Auth/EmailVerificationTest.php` → `tests/Archived/Auth/EmailVerificationTest.php`
3. `tests/Feature/Auth/PasswordConfirmationTest.php` → `tests/Archived/Auth/PasswordConfirmationTest.php`
4. `tests/Feature/Auth/PasswordResetTest.php` → `tests/Archived/Auth/PasswordResetTest.php`
5. `tests/Feature/Auth/RegistrationTest.php` → `tests/Archived/Auth/RegistrationTest.php`
6. `tests/Feature/Auth/TwoFactorChallengeTest.php` → `tests/Archived/Auth/TwoFactorChallengeTest.php`
7. `tests/Feature/Auth/VerificationNotificationTest.php` → `tests/Archived/Auth/VerificationNotificationTest.php`

**Settings Tests** (3 files): 8. `tests/Feature/Settings/PasswordUpdateTest.php` → `tests/Archived/Settings/PasswordUpdateTest.php` 9. `tests/Feature/Settings/ProfileUpdateTest.php` → `tests/Archived/Settings/ProfileUpdateTest.php` 10. `tests/Feature/Settings/TwoFactorAuthenticationTest.php` → `tests/Archived/Settings/TwoFactorAuthenticationTest.php`

**Dashboard Test** (1 file): 11. `tests/Feature/DashboardTest.php` → `tests/Archived/DashboardTest.php`

---

## PowerShell Commands

```powershell
# 1. Delete template tests
Remove-Item tests/Feature/ExampleTest.php
Remove-Item tests/Unit/ExampleTest.php

# 2. Delete Feature tests (content will be merged to Browser)
Remove-Item tests/Feature/StationToolTest.php
Remove-Item tests/Feature/StationMapPageTest.php

# 3. Delete redundant Browser test (after reviewing)
Remove-Item tests/Browser/StationToolRefactorTest.php

# 4. Archive auth/settings tests
New-Item -ItemType Directory -Path tests/Archived/Auth -Force
New-Item -ItemType Directory -Path tests/Archived/Settings -Force
Move-Item tests/Feature/Auth/*.php tests/Archived/Auth/
Move-Item tests/Feature/Settings/*.php tests/Archived/Settings/
Move-Item tests/Feature/DashboardTest.php tests/Archived/
Remove-Item tests/Feature/Auth -Recurse
Remove-Item tests/Feature/Settings -Recurse

# 5. Verify
php artisan test
composer run precommit
```

---

## Notes for StationToolTest.php Merge

When merging into `tests/Browser/StationToolTest.php`:

1. **Keep existing browser interaction tests**
2. **From Feature/StationToolTest.php, add**:
    - Tests that verify Inertia page renders with correct props
    - Tests for URL query parameter parsing (stations)
    - Add `Http::fake(AemetFixtures::httpFakeConfig())` to prevent skipOnCi

3. **From Feature/StationMapPageTest.php, add**:
    - Map rendering tests
    - Station display tests
    - Add `Http::fake(AemetFixtures::httpFakeConfig())`

4. **From Browser/StationToolRefactorTest.php (if unique)**:
    - Extract any unique tests and add with mocked data

5. **Remove all** `->skipOnCi()` markers since all tests will use mocked data

---

**Estimated Time**: 20-30 minutes  
**Status**: Ready to execute  
**Risk Level**: LOW ✅
