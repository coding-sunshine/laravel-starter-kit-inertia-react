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
| [CaptureLeadAction](docs/developer/backend/actions/CaptureLeadAction.md) | N/A | ✅ |
| [EnrollInNurtureSequenceAction](docs/developer/backend/actions/EnrollInNurtureSequenceAction.md) | N/A | ✅ |
| [GenerateColdOutreachAction](docs/developer/backend/actions/GenerateColdOutreachAction.md) | N/A | ✅ |
| [GenerateLandingPageCopyAction](docs/developer/backend/actions/GenerateLandingPageCopyAction.md) | N/A | ✅ |
| [GenerateLeadBriefAction](docs/developer/backend/actions/GenerateLeadBriefAction.md) | N/A | ✅ |
| [RouteLeadAction](docs/developer/backend/actions/RouteLeadAction.md) | N/A | ✅ |
| [AdvanceFunnelInstanceAction](../docs/developer/backend/actions/AdvanceFunnelInstanceAction.md) | N/A | ✅ |
| [GenerateAiSummaryAction](../docs/developer/backend/actions/GenerateAiSummaryAction.md) | N/A | ✅ |
| [GeneratePredictiveSuggestionsAction](../docs/developer/backend/actions/GeneratePredictiveSuggestionsAction.md) | N/A | ✅ |
| [GeneratePropertyDescriptionAction](../docs/developer/backend/actions/GeneratePropertyDescriptionAction.md) | N/A | ✅ |
| [ValidateListingAction](./ValidateListingAction.md) | N/A | ✅ |
| [CreateListingVersionAction](./CreateListingVersionAction.md) | N/A | ✅ |
| [SuggestPushTimeAction](./SuggestPushTimeAction.md) | N/A | ✅ |
| [ImportInventoryAction](./ImportInventoryAction.md) | N/A | ✅ |
| [AddMentionAction](docs/developer/backend/actions/AddMentionAction.md) | N/A | ✅ |
| [EvaluateAutomationRulesAction](docs/developer/backend/actions/EvaluateAutomationRulesAction.md) | N/A | ✅ |
| [ProcessAutomationRuleAction](docs/developer/backend/actions/ProcessAutomationRuleAction.md) | N/A | ✅ |
| [GenerateDealForecastAction](docs/developer/backend/actions/GenerateDealForecastAction.md) | N/A | ✅ |
| [NlAnalyticsQueryAction](docs/developer/backend/actions/NlAnalyticsQueryAction.md) | N/A | ✅ |
| GenerateAdCopyAction | N/A | ❌ |
| GenerateBrochureV2Action | N/A | ❌ |
| GenerateEmailCampaignAction | N/A | ❌ |
| GenerateLandingPageAction | N/A | ❌ |


