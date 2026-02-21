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
| [CalculateDemurrageCharges](./calculatedemurragecharges.md) | N/A | ✅ |
| [ConfirmVehicleUnload](./confirmvehicleunload.md) | N/A | ✅ |
| [CreateIndent](./createindent.md) | N/A | ✅ |
| [CreateRake](./createrake.md) | N/A | ✅ |
| [CreateVehicleArrival](./createvehiclearrival.md) | N/A | ✅ |
| [GenerateReports](./generatereports.md) | N/A | ✅ |
| [OptimizePerformance](./optimizeperformance.md) | N/A | ✅ |
| [ProcessGuardInspection](./processguardinspection.md) | N/A | ✅ |
| [ProcessRrDocument](./processrrdocument.md) | N/A | ✅ |
| [ReconcileRakeAction](./reconcilerakeaction.md) | N/A | ✅ |
| [ReconcileRrData](./reconcilerrdata.md) | N/A | ✅ |
| [RunReportAction](./runreportaction.md) | N/A | ✅ |
| [SyncDemurrageAlertsAction](./syncdemurragealertsaction.md) | N/A | ✅ |
| [UatUtilities](./uatutilities.md) | N/A | ✅ |
| [UpdateStockLedger](./updatestockledger.md) | N/A | ✅ |
| [ImportRakeDataFromExcelAction](./importrakedatafromexcelaction.md) | N/A | ✅ |
| [PurchaseCreditsAction](./purchasecreditsaction.md) | N/A | ✅ |
| [SyncSubscriptionSeatsAction](./syncsubscriptionseatsaction.md) | N/A | ✅ |
| [CreateNewUser](./createnewuser.md) | N/A | ✅ |
| [ResetUserPassword](./resetuserpassword.md) | N/A | ✅ |
| [UpdateUserProfileInformation](./updateuserprofileinformation.md) | N/A | ✅ |
| [AggregateSidingPerformance](./aggregatesidingperformance.md) | N/A | ✅ |
| [GenerateDailyBriefingAction](./generatedailybriefingaction.md) | N/A | ✅ |
| [GeneratePenaltyInsightsAction](./generatepenaltyinsightsaction.md) | N/A | ✅ |
| [BuildPenaltyChartDataAction](./BuildPenaltyChartDataAction.md) | N/A | ✅ |


