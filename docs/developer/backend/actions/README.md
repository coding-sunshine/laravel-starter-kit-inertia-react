# Actions

Actions are single-purpose classes that encapsulate business logic. They live in `app/Actions/` and follow a consistent pattern.

## Pattern

All Actions:
- Have a single `handle()` method
- Are `final readonly` classes
- Accept dependencies via constructor
- Return typed values

## Available Actions

| Action | Purpose | Documented |
|--------|---------|------------|
| [CreateUser](./CreateUser.md) | N/A | ✅ |
| [CreateUserEmailResetNotification](./CreateUserEmailResetNotification.md) | N/A | ✅ |
| [CreateUserEmailVerificationNotification](./CreateUserEmailVerificationNotification.md) | N/A | ✅ |
| [CreateUserPassword](./CreateUserPassword.md) | N/A | ✅ |
| [DeleteUser](./DeleteUser.md) | N/A | ✅ |
| [UpdateUser](./UpdateUser.md) | N/A | ✅ |
| [UpdateUserPassword](./UpdateUserPassword.md) | N/A | ✅ |
| [LoggingEnableTwoFactorAuthentication](./LoggingEnableTwoFactorAuthentication.md) | N/A | ✅ |
| [LoggingDisableTwoFactorAuthentication](./LoggingDisableTwoFactorAuthentication.md) | N/A | ✅ |
| [LoggingConfirmTwoFactorAuthentication](./LoggingConfirmTwoFactorAuthentication.md) | N/A | ✅ |
| [LoggingGenerateNewRecoveryCodes](./LoggingGenerateNewRecoveryCodes.md) | N/A | ✅ |
| [StoreContactSubmission](./StoreContactSubmission.md) | N/A | ✅ |
| [CompleteOnboardingAction](./CompleteOnboardingAction.md) | N/A | ✅ |
| [RateHelpArticleAction](./RateHelpArticleAction.md) | N/A | ✅ |
| [AcceptOrganizationInvitationAction](./acceptorganizationinvitationaction.md) | N/A | ✅ |
| [CreateOrganizationAction](./createorganizationaction.md) | N/A | ✅ |
| [CreatePersonalOrganizationForUserAction](./createpersonalorganizationforuseraction.md) | N/A | ✅ |
| [InviteToOrganizationAction](./invitetoorganizationaction.md) | N/A | ✅ |
| [RemoveOrganizationMemberAction](./removeorganizationmemberaction.md) | N/A | ✅ |
| [SwitchOrganizationAction](./switchorganizationaction.md) | N/A | ✅ |
| [TransferOrganizationOwnershipAction](./transferorganizationownershipaction.md) | N/A | ✅ |
| [GetRequiredTermsVersionsForUser](./GetRequiredTermsVersionsForUser.md) | N/A | ✅ |
| [RecordTermsAcceptance](./RecordTermsAcceptance.md) | N/A | ✅ |
| [StoreEnterpriseInquiryAction](./StoreEnterpriseInquiryAction.md) | N/A | ✅ |
| [BulkSoftDeleteUsers](./BulkSoftDeleteUsers.md) | N/A | ✅ |
| [DuplicateUser](./DuplicateUser.md) | N/A | ✅ |
| [UpdateUserThemeMode](docs/developer/backend/actions/UpdateUserThemeMode.md) | N/A | ✅ |
| [SuggestThemeFromLogo](docs/developer/backend/actions/SuggestThemeFromLogo.md) | N/A | ✅ |
| [RecordAuditLog](docs/developer/backend/actions/RecordAuditLog.md) | N/A | ✅ |
| [VerifyCustomDomain](docs/developer/backend/actions/VerifyCustomDomain.md) | N/A | ✅ |
| [FindOrCreateSocialUser](./FindOrCreateSocialUser.md) | N/A | ✅ |
| [BatchUpdateUsersAction](docs/developer/backend/actions/BatchUpdateUsersAction.md) | N/A | ✅ |
| [GenerateFlyerAction](docs/developer/backend/actions/GenerateFlyerAction.md) | N/A | ✅ |
| [UpdateLastContactedAtAction](docs/developer/backend/actions/UpdateLastContactedAtAction.md) | N/A | ✅ |
| [UpdateContactStageAction](docs/developer/backend/actions/UpdateContactStageAction.md) | N/A | ✅ |
| [UpdateSaleStageAction](docs/developer/backend/actions/UpdateSaleStageAction.md) | N/A | ✅ |
| [BulkUpdateContactsAction](docs/developer/backend/actions/BulkUpdateContactsAction.md) | N/A | ✅ |
| [BulkUpdateReservationsAction](docs/developer/backend/actions/BulkUpdateReservationsAction.md) | N/A | ✅ |
| [PushListingAction](docs/developer/backend/actions/PushListingAction.md) | N/A | ✅ |
| CaptureLeadAction | N/A | ❌ |
| EnrollInNurtureSequenceAction | N/A | ❌ |
| GenerateColdOutreachAction | N/A | ❌ |
| GenerateLandingPageCopyAction | N/A | ❌ |
| GenerateLeadBriefAction | N/A | ❌ |
| RouteLeadAction | N/A | ❌ |


