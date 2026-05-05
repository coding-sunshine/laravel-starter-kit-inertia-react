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
| [ResolveRakeForRrImportPreview](./resolverakeforrrimportpreview.md) | Resolve indent and rake by FNR for RR upload preview | ✅ |
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
| [BuildDispatchReportDprExport](./BuildDispatchReportDprExport.md) | N/A | ✅ |
| [BuildPenaltyChartDataAction](./BuildPenaltyChartDataAction.md) | N/A | ✅ |
| [CalculateSidingRiskScoresAction](./CalculateSidingRiskScoresAction.md) | N/A | ✅ |
| [ClassifyPenaltyRootCauseAction](./ClassifyPenaltyRootCauseAction.md) | N/A | ✅ |
| [GeneratePenaltyPredictionsAction](./GeneratePenaltyPredictionsAction.md) | N/A | ✅ |
| [RecommendDisputeAction](./RecommendDisputeAction.md) | N/A | ✅ |
| [CreateUnloadFromArrival](./createunloadfromarrival.md) | N/A | ✅ |
| [GenerateDispatchReport](./generatedispatchreport.md) | N/A | ✅ |
| [ApplyDemurragePenaltyAction](./applydemurragepenaltyaction.md) | N/A | ✅ |
| [ApplyWeighmentPenaltiesAction](./applyweighmentpenaltiesaction.md) | N/A | ✅ |
| [CreateApiUser](./createapiuser.md) | N/A | ✅ |
| [EndTxrAction](./endtxraction.md) | N/A | ✅ |
| [StartTxrAction](./starttxraction.md) | N/A | ✅ |
| [StoreTyrUnfitLogsAction](./storetyrunfitlogsaction.md) | N/A | ✅ |
| [UpdateTxrHeaderAction](./updatetxrheaderaction.md) | N/A | ✅ |
| [ImportHistoricalMinesDataFromExcelAction](./importhistoricalminesdatafromexcelaction.md) | N/A | ✅ |
| DeleteIndentAction | N/A | ❌ |
| DeleteRrDocumentAction | N/A | ❌ |
| DeleteStandaloneHistoricalWeighmentAction | N/A | ❌ |
| ImportSidingDispatchFromExcel | N/A | ❌ |
| [ProvisionRakeForIndent](docs/developer/backend/actions/provisionrakeforindent.md) | N/A | ✅ |
| SyncTxrUnfitFlagsToWagonsAction | N/A | ❌ |
| [RecordManualRakeWeighment](./recordmanualrakeweighment.md) | N/A | ✅ |
| [UpdateManualRakeWeighment](./updatemanualrakeweighment.md) | N/A | ✅ |
| CreateIndentAndProvisionRakeAction | N/A | ❌ |
| ApplyPloPenaltyAction | N/A | ❌ |
| CalculatePloPenaltyAction | N/A | ❌ |
| FetchRakeWeighmentFromRailwayReceipt | N/A | ❌ |
| ForceMajeureStitchOutcome | N/A | ❌ |
| GenerateLoadingRecommendationAction | N/A | ❌ |
| LogLoadingOverride | N/A | ❌ |
| PloPenaltyResult | N/A | ❌ |
| ReconcilePenaltyHeadsAction | N/A | ❌ |
| ReconciliationOutcome | N/A | ❌ |
| CommandPaletteResults | N/A | ❌ |
| SearchForCommandPaletteAction | N/A | ❌ |
| StitchForceMajeureDisputesAction | N/A | ❌ |


