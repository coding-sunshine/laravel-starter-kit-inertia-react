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
| [SendPasswordResetOtp](./SendPasswordResetOtp.md) | N/A | ✅ |
| [VerifyPasswordResetOtp](./VerifyPasswordResetOtp.md) | N/A | ✅ |
| [ResetPasswordWithOtp](./ResetPasswordWithOtp.md) | N/A | ✅ |
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
| [ResolveRakeForRrImportPreview](./resolverakeforrrimportpreview.md) | N/A | ✅ |
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
| [RecalculateDailySidingVehicleDispatchRollups](./recalculatedailysidingvehicledispatchrollups.md) | N/A | ✅ |
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
| [DeleteIndentAction](./deleteindentaction.md) | N/A | ✅ |
| [DeleteRrDocumentAction](./deleterrdocumentaction.md) | N/A | ✅ |
| [DeleteStandaloneHistoricalWeighmentAction](./deletestandalonehistoricalweighmentaction.md) | N/A | ✅ |
| [ImportSidingDispatchFromExcel](./importsidingdispatchfromexcel.md) | N/A | ✅ |
| [ProvisionRakeForIndent](docs/developer/backend/actions/provisionrakeforindent.md) | N/A | ✅ |
| [SyncTxrUnfitFlagsToWagonsAction](./synctxrunfitflagstowagonsaction.md) | N/A | ✅ |
| [RecordManualRakeWeighment](./recordmanualrakeweighment.md) | N/A | ✅ |
| [UpdateManualRakeWeighment](./updatemanualrakeweighment.md) | N/A | ✅ |
| [CreateIndentAndProvisionRakeAction](./createindentandprovisionrakeaction.md) | N/A | ✅ |
| [ApplyPloPenaltyAction](./applyplopenaltyaction.md) | N/A | ✅ |
| [CalculatePloPenaltyAction](./calculateplopenaltyaction.md) | N/A | ✅ |
| [FetchRakeWeighmentFromRailwayReceipt](./fetchrakeweighmentfromrailwayreceipt.md) | N/A | ✅ |
| [ForceMajeureStitchOutcome](./forcemajeurestitchoutcome.md) | N/A | ✅ |
| [GenerateLoadingRecommendationAction](./generateloadingrecommendationaction.md) | N/A | ✅ |
| [LogLoadingOverride](./logloadingoverride.md) | N/A | ✅ |
| [PloPenaltyResult](./plopenaltyresult.md) | N/A | ✅ |
| [ReconcilePenaltyHeadsAction](./reconcilepenaltyheadsaction.md) | N/A | ✅ |
| [ReconciliationOutcome](./reconciliationoutcome.md) | N/A | ✅ |
| [CommandPaletteResults](./commandpaletteresults.md) | N/A | ✅ |
| [SearchForCommandPaletteAction](./searchforcommandpaletteaction.md) | N/A | ✅ |
| [StitchForceMajeureDisputesAction](./stitchforcemajeuredisputesaction.md) | N/A | ✅ |
| [PreviewRailwayReceiptImport](./previewrailwayreceiptimport.md) | N/A | ✅ |
| [BuildManagerBrief](../actions/manager-brief.md) | N/A | ✅ |
| [DeduplicateVehicleWorkordersByVehicleNoAction](./deduplicatevehicleworkordersbyvehiclenoaction.md) | N/A | ✅ |
| [ImportTransportWorkOrderRegistrationsFromExcelAction](./importtransportworkorderregistrationsfromexcelaction.md) | N/A | ✅ |
| [ImportVehicleWorkordersFromVehiclesSpreadsheetAction](./importvehicleworkordersfromvehiclesspreadsheetaction.md) | N/A | ✅ |
| [CollectSignals](../actions/manager-brief.md) | N/A | ✅ |
| [RankSignals](../actions/manager-brief.md) | N/A | ✅ |
| [MapTransportRegistrationToVehicleWorkorderDefaults](./maptransportregistrationtovehicleworkorderdefaults.md) | N/A | ✅ |
| [RecalculateRakeWeighmentOverload](./recalculaterakeweighmentoverload.md) | N/A | ✅ |
| [SyncLoadriteEvent](./syncloadriteevent.md) | N/A | ✅ |
| [RecalculateDailyVehicleEntryRollups](./recalculatedailyvehicleentryrollups.md) | N/A | ✅ |


