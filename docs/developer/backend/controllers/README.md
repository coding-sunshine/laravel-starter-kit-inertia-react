# Controllers

Controllers handle HTTP requests and coordinate between routes, Actions, and Inertia pages.

## Pattern

All Controllers:
- Are `final readonly` classes
- Use type-hinted dependencies
- Return Inertia responses or redirects
- Use Form Requests for validation

## Available Controllers

| Controller | Purpose | Documented |
|------------|---------|------------|
| [BlogController](./BlogController.md) | N/A | ✅ |
| [ChangelogController](./ChangelogController.md) | N/A | ✅ |
| [CoalTransportReportExportController](./CoalTransportReportExportController.md) | N/A | ✅ |
| [DispatchReportDprExportController](./DispatchReportDprExportController.md) | N/A | ✅ |
| [HelpCenterController](./HelpCenterController.md) | N/A | ✅ |
| [RateHelpArticleController](./RateHelpArticleController.md) | N/A | ✅ |
| [SessionController](./SessionController.md) | N/A | ✅ |
| [UserController](./UserController.md) | N/A | ✅ |
| [UserEmailResetNotificationController](./UserEmailResetNotificationController.md) | N/A | ✅ |
| [UserEmailVerificationController](./UserEmailVerificationController.md) | N/A | ✅ |
| [UserEmailVerificationNotificationController](./UserEmailVerificationNotificationController.md) | N/A | ✅ |
| [UserPasswordController](./UserPasswordController.md) | N/A | ✅ |
| [UserProfileController](./UserProfileController.md) | N/A | ✅ |
| [UserTwoFactorAuthenticationController](./UserTwoFactorAuthenticationController.md) | N/A | ✅ |
| [ProfileExportPdfController](./ProfileExportPdfController.md) | N/A | ✅ |
| [ContactSubmissionController](./ContactSubmissionController.md) | N/A | ✅ |
| [CookieConsentController](./CookieConsentController.md) | N/A | ✅ |
| [PersonalDataExportController](./PersonalDataExportController.md) | N/A | ✅ |
| [OnboardingController](./OnboardingController.md) | N/A | ✅ |
| [InvitationAcceptController](./invitationacceptcontroller.md) | N/A | ✅ |
| [OrganizationController](./organizationcontroller.md) | N/A | ✅ |
| [OrganizationInvitationController](./organizationinvitationcontroller.md) | N/A | ✅ |
| [OrganizationMemberController](./organizationmembercontroller.md) | N/A | ✅ |
| [OrganizationSwitchController](./organizationswitchcontroller.md) | N/A | ✅ |
| [TermsAcceptController](./TermsAcceptController.md) | N/A | ✅ |
| [EnterpriseInquiryController](./EnterpriseInquiryController.md) | N/A | ✅ |
| [Controller](./controller.md) | N/A | ✅ |
| [ChatController](./chatcontroller.md) | N/A | ✅ |
| [AlertController](./alertcontroller.md) | N/A | ✅ |
| [BillingDashboardController](./billingdashboardcontroller.md) | N/A | ✅ |
| [CreditController](./creditcontroller.md) | N/A | ✅ |
| [InvoiceController](./invoicecontroller.md) | N/A | ✅ |
| [PaddleWebhookController](./paddlewebhookcontroller.md) | N/A | ✅ |
| [PricingController](./pricingcontroller.md) | N/A | ✅ |
| [StripeWebhookController](./stripewebhookcontroller.md) | N/A | ✅ |
| [ExecutiveDashboardController](./executivedashboardcontroller.md) | N/A | ✅ |
| [IndentsController](./indentscontroller.md) | N/A | ✅ |
| [PenaltyController](./penaltycontroller.md) | N/A | ✅ |
| [RrDocumentController](./rrdocumentcontroller.md) | N/A | ✅ |
| [RakeGuardInspectionController](./rakeguardinspectioncontroller.md) | N/A | ✅ |
| [RakeTxrController](./raketxrcontroller.md) | N/A | ✅ |
| [RakeWeighmentController](./rakeweighmentcontroller.md) | N/A | ✅ |
| [RakesController](./rakescontroller.md) | N/A | ✅ |
| [PowerPlantReceiptController](./powerplantreceiptcontroller.md) | N/A | ✅ |
| [ReconciliationController](./reconciliationcontroller.md) | N/A | ✅ |
| [ReportsController](./reportscontroller.md) | N/A | ✅ |
| [VehicleArrivalController](./vehiclearrivalcontroller.md) | N/A | ✅ |
| [VehicleUnloadController](./vehicleunloadcontroller.md) | N/A | ✅ |
| [AchievementsController](./achievementscontroller.md) | N/A | ✅ |
| [SidingSwitchController](./sidingswitchcontroller.md) | N/A | ✅ |
| [RakeWagonController](./rakewagoncontroller.md) | N/A | ✅ |
| [DailyVehicleEntryController](./dailyvehicleentrycontroller.md) | N/A | ✅ |
| [LoadersController](./loaderscontroller.md) | N/A | ✅ |
| [PenaltyTypesController](./penaltytypescontroller.md) | N/A | ✅ |
| [PowerPlantApiController](./powerplantapicontroller.md) | N/A | ✅ |
| [PowerPlantController](./powerplantcontroller.md) | N/A | ✅ |
| [PowerplantSidingDistancesController](./powerplantsidingdistancescontroller.md) | N/A | ✅ |
| [RakeLoadController](./rakeloadcontroller.md) | N/A | ✅ |
| [SectionTimersController](./sectiontimerscontroller.md) | N/A | ✅ |
| [SidingsController](./sidingscontroller.md) | N/A | ✅ |
| [TxrController](./txrcontroller.md) | N/A | ✅ |
| [WagonUnfitController](./wagonunfitcontroller.md) | N/A | ✅ |
| [GenerateDispatchReportController](./generatedispatchreportcontroller.md) | N/A | ✅ |
| [DailySidingVehicleDispatchRollupAdminController](./dailysidingvehicledispatchrollupadmincontroller.md) | Super-admin daily siding dispatch rollup inspector | ✅ |
| [VehicleDispatchController](./vehicledispatchcontroller.md) | N/A | ✅ |
| [VehicleWorkorderController](./vehicleworkordercontroller.md) | N/A | ✅ |
| [MobileDashboardController](./mobiledashboardcontroller.md) | N/A | ✅ |
| [AuthController](./authcontroller.md) | N/A | ✅ |
| [IndentController](./indentcontroller.md) | N/A | ✅ |
| [RailwayReceiptApiController](./railwayreceiptapicontroller.md) | N/A | ✅ |
| [RailwayReceiptUploadController](./railwayreceiptuploadcontroller.md) | N/A | ✅ |
| [RakeRrDiversionApiController](./rakerrdiversionapicontroller.md) | N/A | ✅ |
| [RakeRrHubStateApiController](./rakerrhubstateapicontroller.md) | N/A | ✅ |
| [RakeController](./rakecontroller.md) | N/A | ✅ |
| [RakeWeighmentApiController](./rakeweighmentapicontroller.md) | N/A | ✅ |
| [RolePermissionController](./rolepermissioncontroller.md) | N/A | ✅ |
| [SidingController](./sidingcontroller.md) | N/A | ✅ |
| [SidingVehicleDispatchController](./sidingvehicledispatchcontroller.md) | N/A | ✅ |
| [WeighmentUploadController](./weighmentuploadcontroller.md) | N/A | ✅ |
| [HistoricalMineController](./historicalminecontroller.md) | N/A | ✅ |
| [HistoricalRakeController](./historicalrakecontroller.md) | N/A | ✅ |
| [OpeningCoalStockController](./openingcoalstockcontroller.md) | N/A | ✅ |
| [ProductionEntryController](./productionentrycontroller.md) | N/A | ✅ |
| [RrUploadController](./rruploadcontroller.md) | N/A | ✅ |
| [RailwaySidingEmptyWeighmentController](./railwaysidingemptyweighmentcontroller.md) | N/A | ✅ |
| [ShiftTimingsController](./shifttimingscontroller.md) | N/A | ✅ |
| [WeighmentsController](./weighmentscontroller.md) | N/A | ✅ |
| AccountDeletionRequestController | N/A | ❌ |
| CoalStockApproxDetailController | N/A | ❌ |
| RedirectAdminHomeController | N/A | ❌ |
| NotificationReadController | N/A | ❌ |
| PreRrController | N/A | ❌ |
| RakeDiverrtDestinationController | N/A | ❌ |
| RakeDiversionModeController | N/A | ❌ |
| RakePowerPlantReceiptController | N/A | ❌ |
| RakeLoaderController | N/A | ❌ |
| RakeWeighmentWorkflowApiController | N/A | ❌ |
| LoaderOperatorsController | N/A | ❌ |
| RakeRrHubStateController | N/A | ❌ |
| SidingPreIndentReportController | N/A | ❌ |
| [StockLedgerController](./stockledgercontroller.md) | N/A | ✅ |
| [LoaderOverloadWebController](./loaderoverloadwebcontroller.md) | N/A | ✅ |
| LiveMonitorController | N/A | ❌ |
| CommandPaletteSearchController | N/A | ❌ |
| QuickPlacementController | N/A | ❌ |
| SidingMonitorController | N/A | ❌ |
| [RailwayReceiptImportPreviewController](./railwayreceiptimportpreviewcontroller.md) | N/A | ✅ |


