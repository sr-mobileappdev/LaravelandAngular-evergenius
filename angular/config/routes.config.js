export function RoutesConfig($stateProvider, $urlRouterProvider, $localStorageProvider) {
    'ngInject'

    var getView = (viewName) => {
        if (viewName) {
            $localStorageProvider.set('IsRefresh', 0);
        }
        return `./views/app/pages/${viewName}/${viewName}.page.html`
    }

    var getLayout = (layout) => {
        return `./views/app/pages/layout/${layout}.page.html`
    }

    $urlRouterProvider.otherwise('/')

    $stateProvider
        .state('app', {
            abstract: true,
            views: {
                'layout': {
                    templateUrl: getLayout('layout')
                },
                'header@app': {
                    templateUrl: getView('header')
                },
                'footer@app': {
                    templateUrl: getView('footer')
                },
                main: {}
            },
            data: {
                bodyClass: 'hold-transition skin-blue sidebar-mini'
            }
        })

        .state('app.socialconnectprofiles', {
            url: '/social-connect-profiles',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<social-connect-profiles></social-connect-profiles>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                multiple_pages: null
            }
        })


        .state('app.email-marketing', {
            url: '/email-marketing',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<email-marketing></email-marketing>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                multipleListInfo: null
            }
        })

        .state('app.email-campaigns', {
            url: '/email-campaigns',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<email-campaigns></email-campaigns>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.marketing-contacts', {
            url: '/email-marketing/contacts/list/:contactId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contact-listings></contact-listings>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                list_info: null,
            }
        })
        .state('app.emimport-contacts', {
            url: '/email-marketing/import-contacts',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<import-contacts></import-contacts>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                list_Obj: null,
            }
        })

        .state('app.emcontact-mapping', {
            url: '/email-marketing/contact-mapping',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<importcontacts-info></importcontacts-info>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                Obj: null
            }
        })

        .state('app.campaigns-view', {
            url: '/email-marketing/campaigns-view/{campaign_id}',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<view-campaigns></view-campaigns>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })

        .state('app.save-mapped-contacts', {
            url: '/email-marketing/mapped-contacts',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<importcontactsinfo-submit></importcontactsinfo-submit>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        //actionFunnel
        .state('app.new-campaigns', {
            url: '/new-campaigns/{campaign_id}',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<new-campaigns></new-campaigns>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                status: { status },
                campaign_id: null,
                list_info: null,
                multipleListInfo: null
            }
        })
        .state('app.edit-campaigns', {
            url: '/edit-campaigns/{campaign_id}',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-campaigns></edit-campaigns>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                campaign_id: null
            }
        })
        .state('app.action-funnel', {
            url: '/action-funnel',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<action-funnel></action-funnel>'
                }
            },
            params: {
                alerts: null,
                roleId: null,

            }
        })
        .state('app.funnel-send-detail', {
            url: '/funnel-send-detail',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<send-detail></send-detail>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                type: null,
                status: null,
                count: null,
                step_name: null

            }
        })
        .state('app.funnel-send-sms', {
            url: '/funnel-send-sms',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<send-sms></send-sms>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                type: null,
                status: null,
                count: null,
                step_name: null

            }
        })
        .state('app.funnel-step-action-type', {
            url: '/funnel-step-action-type',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<action-type></action-type>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                type: null,
                status: null,
                count: null,
                step_name: null

            }
        })
        .state('app.action-funnel-details', {
            url: '/action-funnel-details/:funnelId/step/:id',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<actionfunnel-detail></actionfunnel-detail>'
                }
            },
            params: {
                alerts: null,
                funnelId: null,
                reload: true,
                id: null,
                type: null,
                status: null

            }
        })

        .state('app.socialposts', {
            url: '/social-posts',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<social-posts></social-posts>'
                }
            },
            params: {
                alerts: null,
                message: null,
                roleId: null
            }
        })
        .state('app.socialpostsadd', {
            url: '/social-posts-add',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<social-posts-add></social-posts-add>'
                }
            },
            params: {
                alerts: null,
                edit_data: null,
                generate_content: null,
                roleId: null
            }
        })

        .state('app.agentdashboard', {
            url: '/agent-dashboard',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<agent-dashboard></agent-dashboard>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                multiple_pages: null
            }
        })
        .state('app.socialgeneratecontent', {
            url: '/social-generate-content',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<social-generate-content></social-generate-content>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.socialhistory', {
            url: '/social-history',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<social-history></social-history>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })

        .state('app.reviewssettings', {
            url: '/reviews/settings',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<review-settings></review-settings>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.embedcodegenerate', {
            url: '/reviews/embed-code-generate',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<embed-code-generate></embed-code-generate>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.embedCalendar', {
            url: '/calender/embed-calendar/:userId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<embed-calendar></embed-calendar>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                name: null,
                phone: null
            }
        })
        .state('app.requestformgenerate', {
            url: '/reviews/request-form-generate',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<request-form-generate></request-form-generate>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.profilelisting', {
            url: '/profiles',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<profile-listing></profile-listing>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.reviews', {
            url: '/reviews',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<review-listing></review-listing>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.recentactivity', {
            url: '/recent-activity',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<recent-activity></recent-activity>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.conversation', {
            url: '/conversation',
            data: {
                auth: true,
                bodyClass: 'hold-transition skin-blue sidebar-mini page-conversations'
            },
            views: {
                'main@app': {
                    template: '<conversation></conversation>',

                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.analytics', {
            url: '/analytics',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<analytics></analytics>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.perfectaudience', {
            url: '/perfect-audience',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<perfect-audience></perfect-audience>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.marketinganalytics', {
            url: '/marketing-analytics',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<marketing-analytic></marketing-analytic>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        }).state('app.googleanalytics', {
            url: '/google-analytics',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<google-analytics></google-analytics>'
                }
            },
            params: {
                alerts: null,
                roleId: null
            }
        })
        .state('app.provider', {
            url: '/provider',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<provider></provider>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                providerId: null,
                superadmin_dashboard: true
            }
        })
        .state('app.add-clinic', {
            url: '/add-clinic',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<add-clinic></add-clinic>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                superadmin_dashboard: true
            }
        })

        .state('app.edit-clinic', {
            url: '/edit-clinic/:clinicId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-clinic></edit-clinic>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                clinicId: null,
                superadmin_dashboard: true
            }
        })
        .state('app.add-provider', {
            url: '/add-provider',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<add-provider></add-provider>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                superadmin_dashboard: true
            }
        })

        .state('app.edit-provider', {
            url: '/edit-provider/:providerId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-provider></edit-provider>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                providerId: null,
                superadmin_dashboard: true
            }
        })
        .state('app.landing', {
            url: '/',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    templateUrl: getView('landing')
                }
            }
        })
        .state('app.tablessimple', {
            url: '/tables-simple',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<tables-simple></tables-simple>'
                }
            }
        })
        .state('app.connect-google-analytics', {
            url: '/connect-google-analytics/',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<connect-google-analytics></connect-google-analytics>'
                }
            }
        })
        .state('app.uiicons', {
            url: '/ui-icons',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<ui-icons></ui-icons>'
                }
            }
        })
        .state('app.uimodal', {
            url: '/ui-modal',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<ui-modal></ui-modal>'
                }
            }
        })
        .state('app.uitimeline', {
            url: '/ui-timeline',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<ui-timeline></ui-timeline>'
                }
            }
        })
        .state('app.uibuttons', {
            url: '/ui-buttons',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<ui-buttons></ui-buttons>'
                }
            }
        })
        .state('app.uigeneral', {
            url: '/ui-general',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<ui-general></ui-general>'
                }
            }
        })
        .state('app.formsgeneral', {
            url: '/forms-general',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<forms-general></forms-general>'
                }
            }
        })
        .state('app.chartjs', {
            url: '/charts-chartjs',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<charts-chartjs></charts-chartjs>'
                }
            }
        })
        .state('app.comingsoon', {
            url: '/comingsoon',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<coming-soon></coming-soon>'
                }
            }
        })
        .state('app.profile', {
            url: '/profile',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-profile></user-profile>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.blog', {
            url: '/blog',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<blog-editor></blog-editor>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.reputations', {
            url: '/reputations',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<site-reputaions></site-reputaions>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.seo', {
            url: '/seo',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<seo-service></seo-service>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.userlist', {
            url: '/doctors-lists',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-lists></user-lists>'
                }
            }
        })
        .state('app.managestaff', {
            url: '/manage-staff',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<manage-staff></manage-staff>'
                }
            },
            params: {
                alerts: null,
                menuhide: 1
            }
        })
        .state('app.useredit', {
            url: '/user-edit/:userId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-edit></user-edit>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.userinfoedit', {
            url: '/admin/edit-user-info/:userID',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-edit-info></user-edit-info>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,
                userID: null,
                remainData: null

            }
        })
        .state('app.companysettings', {
            url: '/company-settings',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<comapny-settings></comapny-settings>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                menuhide: 1
            }
        })
        .state('app.companysmtpsettings', {
            url: '/company-smtp-settings',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<company-smtp-settings></company-smtp-settings>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                menuhide: 1
            }
        })
        .state('app.companyedit', {
            url: '/admin/company-edit/:companyId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<company-edit></company-edit>'
                }
            },
            params: {
                alerts: null,
                companyId: null
            }
        })
        .state('app.oppertunities', {
            url: '/opportunities',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<oppertunities></oppertunities>'
                }
            }
        })
        .state('app.leaddetails', {
            url: '/lead-details/:lead_id',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<addoppertunities></addoppertunities>'
                }
            },
            params: {
                lead_id: null,
                userId: null
            }
        })
        .state('app.notificationssettings', {
            url: '/notification-settings',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<notification-settings></notification-settings>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                menuhide: 1
            }
        })
        .state('app.docotoradd', {
            url: '/add-doctor',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<add-doctor></add-doctor>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.staffadd', {
            url: '/add-staff',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<add-staff></add-staff>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                menuhide: 1
            }
        })
        .state('app.staffedit', {
            url: '/edit-staff/:userId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-staff></edit-staff>'
                }
            },
            params: {
                alerts: null,
                menuhide: 1
            }
        })
        .state('app.contactedit', {
            url: '/contact-edit/:contactId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contact-edit></contact-edit>'
                }
            },
            params: {
                alerts: null,
                contactId: null
            }
        })
        .state('app.viewappointment', {
            url: '/appointment/:appointmentId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<appointment-view></appointment-view>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.viewcalendar', {
            url: '/calender/:userId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<calendar-user></calendar-user>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                timeStamps: null
            }
        })
        .state('app.editdefaultcalendar', {
            url: '/edit-default-celendar/:userId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-calendar-user></edit-calendar-user>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.viewcontact', {
            url: '/contact/:contactId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contact-view></contact-view>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                current_tab: null
            }
        })
        .state('app.manageroles', {
            url: '/manage-roles',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-roles></user-roles>'
                }
            }
        })
        .state('app.contacts', {
            url: '/contacts',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contacts-list></contacts-list>'
                }
            }
        })
        .state('app.callrecords', {
            url: '/call-records',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<call-records></call-records>'
                }
            }
        })
        .state('app.smsrecords', {
            url: '/sms-records',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<sms-records></sms-records>'
                }
            }
        })
        .state('app.unauthorizedAccess', {
            url: '/unauthorized-access',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<unauthorized-access></unauthorized-access>'
                }
            }
        })
        .state('app.appointments', {
            url: '/appointments',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<appointments-list></appointments-list>'
                }
            }
        })
        .state('app.manageclients', {
            url: '/admin/clients',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<manage-companies-list></manage-companies-list>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true
            }
        })
        .state('app.manageusers', {
            url: '/admin/manage-users',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<manage-users></manage-users>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,
                userID: null
            }
        })
        .state('app.completeprofile', {
            url: '/admin/complete-profile/:companyID',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<complete-profile></complete-profile>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,
                companyData: null,
                companyId: null,
                companyOBJ: null,
                id: null,
                companyID: null
            }
        })
        .state('app.emailtemplates', {
            url: '/admin/email-templates',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<email-template></email-template>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,

            }
        })
        .state('app.companybuildscreen', {
            url: '/admin/company-add/build-website/:id',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<company-build-screen></company-build-screen>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,
                companyOBJ: null,
                id: null,
                companyID: null

            }
        })
        .state('app.loginactivitydetails', {
            url: '/admin/login-activity-details',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<login-activity></login-activity>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,

            }
        })
        .state('app.gethdclinics', {
            url: '/admin/hd-clinics',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<hd-clinics></hd-clinics>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true
            }
        })
        .state('app.importclinics', {
            url: '/admin/import-clinics',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<clinic-import></clinic-import>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true
            }
        })
        .state('app.importproviders', {
            url: '/admin/import-providers',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<providers-import></providers-import>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true
            }
        })
        .state('app.addnewuser', {
            url: '/admin/add-new-user',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<add-new-user></add-new-user>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,
                remainData: null

            }
        })
        .state('app.infusionsoftauth', {
            url: '/admin/infusionsoft',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<infusionsoft-auth></infusionsoft-auth>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true
            }
        })
        .state('app.superdashboard', {
            url: '/admin/dashboard',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<super-dashboard></super-dashboard>'
                }
            },
            params: {
                alerts: null,
                superadmin_dashboard: true,

            }
        })
        .state('app.companyadd', {
            url: '/admin/company-add/:companyID',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<company-add></company-add>'
                }
            },
            params: {
                alerts: null,
                companyData: null,
                id: null,
                companyID: null

            }
        })
        .state('app.buildsite', {
            url: '/admin/build-site/',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<company-site-builder></company-site-builder>'
                }
            },
            params: {
                alerts: null,
                companyId: null,

            }
        })
        .state('app.integration-screen', {
            url: '/admin/integration/account-setup',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<integration-screen></integration-screen>'
                }
            },
            params: {
                alerts: null,
                companyId: null,
                menuhide: 1
            }
        })
        .state('app.userpermissions', {
            url: '/user-permissions',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-permissions></user-permissions>'
                }
            }
        })
        .state('app.userpermissionsadd', {
            url: '/user-permissions-add',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-permissions-add></user-permissions-add>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.importcontactscsv', {
            url: '/import-contatcs',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contatcs-import></contatcs-import>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.userpermissionsedit', {
            url: '/user-permissions-edit/:permissionId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-permissions-edit></user-permissions-edit>'
                }
            },
            params: {
                alerts: null,
                permissionId: null
            }
        })
        .state('app.userrolesadd', {
            url: '/user-roles-add',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-roles-add></user-roles-add>'
                }
            },
            params: {
                alerts: null
            }
        })
        .state('app.userrolesedit', {
            url: '/user-roles-edit/:roleId',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<user-roles-edit></user-roles-edit>'
                }
            },
            params: {
                alerts: null,
                roleId: null,
                menuhide: 1
            }
        })
        .state('app.widgets', {
            url: '/widgets',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<widgets></widgets>'
                }
            }
        })
        .state('login', {
            url: '/login',
            views: {
                'layout': {
                    templateUrl: getView('login')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition login-page'
            },
            params: {
                registerSuccess: null,
                successMsg: null
            }
        })
        .state('404NotFound', {
            url: '/not-found',
            views: {
                'layout': {
                    templateUrl: getView('not-found')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition login-page'
            },
            params: {
                registerSuccess: null,
                successMsg: null
            }
        })
        .state('loginloader', {
            url: '/login-loader',
            views: {
                'layout': {
                    templateUrl: getView('login-loader')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition login-page'
            }
        })
        .state('register', {
            url: '/register',
            views: {
                'layout': {
                    templateUrl: getView('register')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition register-page'
            }
        })
        .state('userverification', {
            url: '/userverification/:status',
            views: {
                'layout': {
                    templateUrl: getView('user-verification')
                }
            },
            data: {
                bodyClass: 'hold-transition login-page'
            },
            params: {
                status: null
            }
        })
        .state('forgot_password', {
            url: '/forgot-password',
            views: {
                'layout': {
                    templateUrl: getView('forgot-password')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition login-page'
            }
        })
        .state('reset_password', {
            url: '/reset-password/:email/:token',
            views: {
                'layout': {
                    templateUrl: getView('reset-password')
                },
                'header@app': {},
                'footer@app': {}
            },
            data: {
                bodyClass: 'hold-transition login-page'
            }
        })
        .state('app.contactadd', {
            url: '/contact-add',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<contact-add></contact-add>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.keywords', {
            url: '/keywords',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<keyword-analytics></keyword-analytics>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.smsbroadcast', {
            url: '/sms-broadcast',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<sms-broadcast></sms-broadcast>'
                }
            },
            params: {
                alerts: null,
                broadcast_id: null
            }
        })
        .state('app.newsmsbroadcast', {
            url: '/new-sms-broadcast',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<new-sms-broadcast></new-sms-broadcast>'
                }
            },
            params: {
                alerts: null,
                broadcast_id: null
            }
        })
        .state('app.editsmsbroadcast', {
            url: '/edit-sms-broadcast/:broadcast_id',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<edit-sms-broadcast></edit-sms-broadcast>'
                }
            },
            params: {
                alerts: null,
                broadcast_id: null
            }
        })
        .state('app.viewsmsbroadcast', {
            url: '/view-sms-broadcast/:broadcast_id',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<view-broadcast></view-broadcast>'
                }
            },
            params: {
                alerts: null,
                broadcast_id: null
            }
        })
        .state('app.opportunitySetting', {
            url: '/opportunity-setting',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<opportunity-setting></opportunity-setting>'
                }
            },
            params: {
                alerts: null,
                userId: null,
                menuhide: 1
            }
        })
        .state('app.agentReport', {
            url: '/agent-report',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<agent-report></agent-report>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
        .state('app.logout', {
            url: '/logout',
            views: {
                'main@app': {
                    controller: function ($rootScope, $scope, $auth, $state, AclService, $window, $location) {
                        $auth.logout().then(function () {
                            var location = $location.path()
                            var isadmin = location.indexOf('admin') == '-1' ? 0 : 1;
                            delete $window.localStorage.admin_companies;
                            delete $window.localStorage.inper_selected_company;
                            delete $window.localStorage.user_company_details;
                            $window.localStorage.clear();
                            if (isadmin) {
                                delete $window.localStorage.super_admin_token;
                                delete $window.localStorage.super_admin_user_data;
                                delete $window.localStorage.satellizer_token;
                                delete $window.localStorage.adminrole;
                                $window.localStorage.clear();
                            }
                            delete $rootScope.me;
                            AclService.flushRoles();
                            AclService.setAbilities({});
                            delete $window.localStorage.sidebar_docotors;
                            delete $window.localStorage.impersonated;
                            delete $window.localStorage.user_data;
                            if ($window.localStorage.adminrole == 'admin.super') {
                                $auth.setToken($window.localStorage.super_admin_token);
                                $window.localStorage.clear();
                            }
                            $state.go('login')
                        })
                    }
                }
            }
        })
        .state('app.twillio-integration', {
            url: '/twillio-integration',
            data: {
                auth: true
            },
            views: {
                'main@app': {
                    template: '<twillio-integration></twillio-integration>'
                }
            },
            params: {
                alerts: null,
                userId: null
            }
        })
}