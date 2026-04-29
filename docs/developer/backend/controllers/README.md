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
| RakeWagonController | N/A | ❌ |
| DailyVehicleEntryController | N/A | ❌ |
| LoadersController | N/A | ❌ |
| PenaltyTypesController | N/A | ❌ |
| PowerPlantController | N/A | ❌ |
| PowerplantSidingDistancesController | N/A | ❌ |
| RakeLoadController | N/A | ❌ |
| SectionTimersController | N/A | ❌ |
| SidingsController | N/A | ❌ |
| TxrController | N/A | ❌ |
| WagonUnfitController | N/A | ❌ |
| GenerateDispatchReportController | N/A | ❌ |
| VehicleDispatchController | N/A | ❌ |
| VehicleWorkorderController | N/A | ❌ |
| AccountDeletionRequestController | N/A | ❌ |
| MobileDashboardController | N/A | ❌ |
| AuthController | N/A | ❌ |
| IndentController | N/A | ❌ |
| PowerPlantApiController | N/A | ❌ |
| RailwayReceiptApiController | N/A | ❌ |
| RailwayReceiptUploadController | N/A | ❌ |
| RakeController | N/A | ❌ |
| RakeRrDiversionApiController | N/A | ❌ |
| RakeRrHubStateApiController | N/A | ❌ |
| RakeWeighmentApiController | N/A | ❌ |
| RakeWeighmentWorkflowApiController | N/A | ❌ |
| RolePermissionController | N/A | ❌ |
| SidingController | N/A | ❌ |
| SidingVehicleDispatchController | N/A | ❌ |
| WeighmentUploadController | N/A | ❌ |
| CoalStockApproxDetailController | N/A | ❌ |
| LoaderOverloadWebController | N/A | ❌ |
| CoalTransportReportExportController | N/A | ❌ |
| DispatchReportDprExportController | N/A | ❌ |
| RedirectAdminHomeController | N/A | ❌ |
| HistoricalMineController | N/A | ❌ |
| HistoricalRakeController | N/A | ❌ |
| LoaderOperatorsController | N/A | ❌ |
| NotificationReadController | N/A | ❌ |
| OpeningCoalStockController | N/A | ❌ |
| ProductionEntryController | N/A | ❌ |
| RrUploadController | N/A | ❌ |
| RailwaySidingEmptyWeighmentController | N/A | ❌ |
| PreRrController | N/A | ❌ |
| RakeDiverrtDestinationController | N/A | ❌ |
| RakeDiversionModeController | N/A | ❌ |
| RakeLoaderController | N/A | ❌ |
| RakePowerPlantReceiptController | N/A | ❌ |
| RakeRrHubStateController | N/A | ❌ |
| ShiftTimingsController | N/A | ❌ |
| SidingPreIndentReportController | N/A | ❌ |
| StockLedgerController | N/A | ❌ |
| WeighmentsController | N/A | ❌ |


