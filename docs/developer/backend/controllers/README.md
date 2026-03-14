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
| [PageController](./pagecontroller.md) | N/A | ✅ |
| [PageViewController](./pageviewcontroller.md) | N/A | ✅ |
| [UsersTableController](./UsersTableController.md) | N/A | ✅ |
| [SearchController](docs/developer/backend/controllers/SearchController.md) | N/A | ✅ |
| [DashboardController](./dashboardcontroller.md) | N/A | ✅ |
| [UserPreferencesController](docs/developer/backend/controllers/UserPreferencesController.md) | N/A | ✅ |
| [OrgThemeController](docs/developer/backend/controllers/OrgThemeController.md) | N/A | ✅ |
| [SocialAuthController](./SocialAuthController.md) | N/A | ✅ |
| [OrgSlugController](docs/developer/backend/controllers/OrgSlugController.md) | N/A | ✅ |
| [OrgDomainsController](docs/developer/backend/controllers/OrgDomainsController.md) | N/A | ✅ |
| [SlugAvailabilityController](docs/developer/backend/controllers/SlugAvailabilityController.md) | N/A | ✅ |
| [CaddyAskController](docs/developer/backend/controllers/CaddyAskController.md) | N/A | ✅ |
| [NotificationPreferencesController](docs/developer/backend/controllers/NotificationPreferencesController.md) | N/A | ✅ |
| [IndexNotificationsController](docs/developer/backend/controllers/Notifications/IndexNotificationsController.md) | N/A | ✅ |
| [MarkNotificationReadController](docs/developer/backend/controllers/Notifications/MarkNotificationReadController.md) | N/A | ✅ |
| [MarkAllNotificationsReadController](docs/developer/backend/controllers/Notifications/MarkAllNotificationsReadController.md) | N/A | ✅ |
| [DeleteNotificationController](docs/developer/backend/controllers/Notifications/DeleteNotificationController.md) | N/A | ✅ |
| [ClearAllNotificationsController](docs/developer/backend/controllers/Notifications/ClearAllNotificationsController.md) | N/A | ✅ |
| [InstallController](docs/developer/backend/controllers/InstallController.md) | N/A | ✅ |
| [HealthController](./healthcontroller.md) | N/A | ✅ |
| [AnnouncementsTableController](./announcementstablecontroller.md) | N/A | ✅ |
| [CategoriesTableController](./categoriestablecontroller.md) | N/A | ✅ |
| [OrganizationsTableController](./organizationstablecontroller.md) | N/A | ✅ |
| [PostsTableController](./poststablecontroller.md) | N/A | ✅ |
| [LotsTableController](./LotsTableController.md) | N/A | ✅ |
| [ProjectsTableController](./ProjectsTableController.md) | N/A | ✅ |
| [ContactController](./ContactController.md) | N/A | ✅ |
| [PropertyReservationController](./PropertyReservationController.md) | N/A | ✅ |
| [PropertyEnquiryController](./PropertyEnquiryController.md) | N/A | ✅ |
| [PropertySearchController](./PropertySearchController.md) | N/A | ✅ |
| [SaleController](./SaleController.md) | N/A | ✅ |
| [CommissionController](./CommissionController.md) | N/A | ✅ |
| [TaskController](./developer/backend/controllers/TaskController.md) | N/A | ✅ |
| [CampaignSiteController](docs/developer/backend/controllers/CampaignSiteController.md) | N/A | ✅ |
| [ReportController](docs/developer/backend/controllers/ReportController.md) | N/A | ✅ |
| [PipelineController](docs/developer/backend/controllers/PipelineController.md) | N/A | ✅ |
| [FunnelController](docs/developer/backend/controllers/FunnelController.md) | N/A | ✅ |
| [MemberListingsController](docs/developer/backend/controllers/MemberListingsController.md) | N/A | ✅ |
| [ColdOutreachController](docs/developer/backend/controllers/ColdOutreachController.md) | N/A | ✅ |
| [LeadCaptureController](docs/developer/backend/controllers/LeadCaptureController.md) | N/A | ✅ |
| [LeadGenerationController](docs/developer/backend/controllers/LeadGenerationController.md) | N/A | ✅ |
| [NurtureSequenceController](docs/developer/backend/controllers/NurtureSequenceController.md) | N/A | ✅ |
| [AiSummaryController](../docs/developer/backend/controllers/AiSummaryController.md) | N/A | ✅ |
| [BotV2Controller](../docs/developer/backend/controllers/BotV2Controller.md) | N/A | ✅ |
| [ConciergeController](../docs/developer/backend/controllers/ConciergeController.md) | N/A | ✅ |
| [FunnelTemplateController](../docs/developer/backend/controllers/FunnelTemplateController.md) | N/A | ✅ |
| [PredictiveSuggestionsController](../docs/developer/backend/controllers/PredictiveSuggestionsController.md) | N/A | ✅ |
| [VapiController](../docs/developer/backend/controllers/VapiController.md) | N/A | ✅ |
| [AgentPortalController](./AgentPortalController.md) | N/A | ✅ |
| [BuilderPortalController](./BuilderPortalController.md) | N/A | ✅ |
| [InventoryApiController](./InventoryApiController.md) | N/A | ✅ |
| [FlyerController](./FlyerController.md) | N/A | ✅ |
| [PuckTemplateController](./PuckTemplateController.md) | N/A | ✅ |
| [PublicSiteController](./PublicSiteController.md) | N/A | ✅ |
| [ProvisionerApiController](./ProvisionerApiController.md) | N/A | ✅ |
| [CustomFieldController](docs/developer/backend/controllers/CustomFieldController.md) | N/A | ✅ |
| [AutomationRuleController](docs/developer/backend/controllers/AutomationRuleController.md) | N/A | ✅ |
| [AnalyticsController](docs/developer/backend/controllers/AnalyticsController.md) | N/A | ✅ |
| [DealForecastController](docs/developer/backend/controllers/DealForecastController.md) | N/A | ✅ |
| AdTemplateController | N/A | ❌ |
| BrochureLayoutController | N/A | ❌ |
| EmailCampaignController | N/A | ❌ |
| LandingPageController | N/A | ❌ |
| RetargetingPixelController | N/A | ❌ |


