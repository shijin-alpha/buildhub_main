# File Tree: buildhub

**Generated:** 3/12/2026, 1:55:29 PM
**Root Path:** `c:\xampp\htdocs\buildhub`

```
├── .kiro
│   └── specs
│       ├── project-state-transition
│       │   └── requirements.md
│       ├── site-inspector-dashboard
│       └── room-improvement-assistant-integration.md
├── ai_service
│   ├── logs
│   ├── models
│   ├── modules
│   │   ├── __init__.py
│   │   ├── conceptual_generator.py
│   │   ├── conceptual_generator_broken.py
│   │   ├── conceptual_generator_fixed.py
│   │   ├── conceptual_generator_simple.py
│   │   ├── object_detector.py
│   │   ├── rule_engine.py
│   │   ├── spatial_analyzer.py
│   │   └── visual_processor.py
│   ├── runs
│   │   └── detect
│   │       ├── predict
│   │       ├── predict10
│   │       ├── predict11
│   │       ├── predict12
│   │       ├── predict13
│   │       ├── predict14
│   │       ├── predict15
│   │       ├── predict16
│   │       ├── predict17
│   │       ├── predict18
│   │       ├── predict19
│   │       ├── predict2
│   │       ├── predict20
│   │       ├── predict21
│   │       ├── predict22
│   │       ├── predict23
│   │       ├── predict24
│   │       ├── predict3
│   │       ├── predict4
│   │       ├── predict5
│   │       ├── predict6
│   │       ├── predict7
│   │       ├── predict8
│   │       └── predict9
│   ├── .env.example
│   ├── debug_test.py
│   ├── diagnose.py
│   ├── install_simple.bat
│   ├── install_windows.bat
│   ├── main.py
│   ├── requirements.txt
│   ├── setup.py
│   ├── start_service.bat
│   ├── start_service.sh
│   └── yolov8n.pt
├── backend
│   ├── api
│   │   ├── admin
│   │   │   ├── add_material.php
│   │   │   ├── add_sample_materials.php
│   │   │   ├── admin_login.php
│   │   │   ├── approve_request.php
│   │   │   ├── assign_site_inspector.php
│   │   │   ├── calculate_real_progress.php
│   │   │   ├── create_materials_table.php
│   │   │   ├── delete_material.php
│   │   │   ├── download_document.php
│   │   │   ├── get_all_users.php
│   │   │   ├── get_inspection_reports.php
│   │   │   ├── get_materials.php
│   │   │   ├── get_pending_payment_verifications.php
│   │   │   ├── get_pending_requests.php
│   │   │   ├── get_pending_users.php
│   │   │   ├── get_site_inspectors.php
│   │   │   ├── get_stats.php
│   │   │   ├── get_support_issues.php
│   │   │   ├── get_support_thread.php
│   │   │   ├── jwt_get_all_users.php
│   │   │   ├── test_email.php
│   │   │   ├── test_session.php
│   │   │   ├── update_user_status.php
│   │   │   ├── user_action.php
│   │   │   ├── verify_payment_receipt.php
│   │   │   └── view_document.php
│   │   ├── architect
│   │   │   ├── cancel_concept_generation.php
│   │   │   ├── create_house_plan.php
│   │   │   ├── create_integrated_house_plan.php
│   │   │   ├── create_layout_library_item.php
│   │   │   ├── delete_concept_preview.php
│   │   │   ├── delete_house_plan.php
│   │   │   ├── download_concept_preview.php
│   │   │   ├── generate_concept_preview.php
│   │   │   ├── get_active_concept_generations.php
│   │   │   ├── get_assigned_requests.php
│   │   │   ├── get_concept_previews.php
│   │   │   ├── get_contractor_house_plans.php
│   │   │   ├── get_house_plan.php
│   │   │   ├── get_house_plans.php
│   │   │   ├── get_layout_library.php
│   │   │   ├── get_layout_requests.php
│   │   │   ├── get_my_designs.php
│   │   │   ├── get_my_layouts.php
│   │   │   ├── get_my_profile.php
│   │   │   ├── get_room_templates.php
│   │   │   ├── process_concept_background.php
│   │   │   ├── regenerate_concept_preview.php
│   │   │   ├── respond_assignment.php
│   │   │   ├── send_inbox_message.php
│   │   │   ├── submit_house_plan.php
│   │   │   ├── submit_house_plan_with_details.php
│   │   │   ├── submit_layout.php
│   │   │   ├── update_house_plan.php
│   │   │   ├── update_layout_library_item.php
│   │   │   ├── upload_design.php
│   │   │   └── upload_house_plan_files.php
│   │   ├── auth
│   │   │   ├── check_session.php
│   │   │   ├── establish_session.php
│   │   │   ├── establish_session_for_payment.php
│   │   │   ├── jwt_login.php
│   │   │   ├── jwt_logout.php
│   │   │   ├── jwt_refresh.php
│   │   │   └── jwt_verify.php
│   │   ├── blockchain
│   │   │   ├── contract_stats.php
│   │   │   ├── get_immutable_audit_trail.php
│   │   │   ├── get_payment_audit_trail.php
│   │   │   ├── health_check.php
│   │   │   ├── payment_recording.php
│   │   │   ├── project_records.php
│   │   │   └── verify_audit_ledger_integrity.php
│   │   ├── chatbot
│   │   │   └── log_interaction.php
│   │   ├── comments
│   │   │   ├── get_comments.php
│   │   │   └── post_comment.php
│   │   ├── common
│   │   │   └── get_payment_blockchain_verification.php
│   │   ├── contractor
│   │   │   ├── acknowledge_inbox_item.php
│   │   │   ├── check_session.php
│   │   │   ├── create_project_from_estimate.php
│   │   │   ├── delete_estimate.php
│   │   │   ├── delete_inbox_item.php
│   │   │   ├── download_layout_images.php
│   │   │   ├── generate_progress_report.php
│   │   │   ├── get_assigned_projects.php
│   │   │   ├── get_available_stages.php
│   │   │   ├── get_available_workers.php
│   │   │   ├── get_completed_project_overruns.php
│   │   │   ├── get_construction_details.php
│   │   │   ├── get_construction_estimates.php
│   │   │   ├── get_construction_timeline.php
│   │   │   ├── get_contractor_projects.php
│   │   │   ├── get_inbox.php
│   │   │   ├── get_layout_requests.php
│   │   │   ├── get_my_estimates.php
│   │   │   ├── get_my_proposals.php
│   │   │   ├── get_payment_history.php
│   │   │   ├── get_pending_payment_verifications.php
│   │   │   ├── get_phase_workers.php
│   │   │   ├── get_progress_analytics.php
│   │   │   ├── get_progress_updates.php
│   │   │   ├── get_project_budget_summary.php
│   │   │   ├── get_project_current_progress.php
│   │   │   ├── get_project_payment_requests.php
│   │   │   ├── get_project_progress.php
│   │   │   ├── get_project_stage_workflow.php
│   │   │   ├── get_projects.php
│   │   │   ├── get_recent_paid_payments.php
│   │   │   ├── get_sent_reports.php
│   │   │   ├── get_stage_documents.php
│   │   │   ├── get_stage_payment_breakdown.php
│   │   │   ├── get_stage_payment_info.php
│   │   │   ├── get_stage_workflow_projects.php
│   │   │   ├── get_submitted_daily_reports.php
│   │   │   ├── jwt_get_my_projects.php
│   │   │   ├── login_contractor_session.php
│   │   │   ├── save_estimate_draft.php
│   │   │   ├── send_acknowledgment_message.php
│   │   │   ├── send_report_to_homeowner.php
│   │   │   ├── serve_layout_image.php
│   │   │   ├── submit_custom_payment_request.php
│   │   │   ├── submit_daily_progress.php
│   │   │   ├── submit_enhanced_progress_update.php
│   │   │   ├── submit_enhanced_stage_payment_request.php
│   │   │   ├── submit_estimate.php
│   │   │   ├── submit_estimate_for_send.php
│   │   │   ├── submit_integrated_progress_report.php
│   │   │   ├── submit_monthly_report.php
│   │   │   ├── submit_payment_request.php
│   │   │   ├── submit_progress_update.php
│   │   │   ├── submit_proposal.php
│   │   │   ├── submit_stage_completion.php
│   │   │   ├── submit_stage_payment_request.php
│   │   │   ├── submit_stage_withdrawal_request.php
│   │   │   ├── submit_weekly_summary.php
│   │   │   ├── update_actual_dates.php
│   │   │   ├── update_planned_schedule.php
│   │   │   ├── update_stage_completion.php
│   │   │   ├── upload_geo_photo.php
│   │   │   ├── upload_simple_receipt.php
│   │   │   ├── upload_stage_documents.php
│   │   │   ├── verify_payment_receipt.php
│   │   │   └── verify_stage_documents.php
│   │   ├── homeowner
│   │   │   ├── analyze_room_improvement.php
│   │   │   ├── assign_architect.php
│   │   │   ├── check_ai_service_health.php
│   │   │   ├── check_image_generation_status.php
│   │   │   ├── check_image_status.php
│   │   │   ├── check_technical_details_access.php
│   │   │   ├── create_notification.php
│   │   │   ├── debug_enhanced_room_improvement.php
│   │   │   ├── debug_room_improvement.php
│   │   │   ├── debug_upload.php
│   │   │   ├── delete_design.php
│   │   │   ├── delete_estimate.php
│   │   │   ├── delete_geo_photo.php
│   │   │   ├── delete_house_plan.php
│   │   │   ├── delete_request.php
│   │   │   ├── establish_session.php
│   │   │   ├── generate_conceptual_image.php
│   │   │   ├── get_all_payment_requests.php
│   │   │   ├── get_architects.php
│   │   │   ├── get_bills_receipts.php
│   │   │   ├── get_concept_previews.php
│   │   │   ├── get_construction_timeline.php
│   │   │   ├── get_contractor_receipts.php
│   │   │   ├── get_contractor_requests.php
│   │   │   ├── get_contractor_stage_documents.php
│   │   │   ├── get_contractors.php
│   │   │   ├── get_dashboard_data.php
│   │   │   ├── get_enhanced_payment_requests.php
│   │   │   ├── get_estimates.php
│   │   │   ├── get_geo_photos.php
│   │   │   ├── get_homeowner_projects.php
│   │   │   ├── get_house_plans.php
│   │   │   ├── get_inspection_reports.php
│   │   │   ├── get_layout_library.php
│   │   │   ├── get_messages.php
│   │   │   ├── get_my_projects.php
│   │   │   ├── get_my_requests.php
│   │   │   ├── get_notifications.php
│   │   │   ├── get_payment_methods.php
│   │   │   ├── get_payment_requests.php
│   │   │   ├── get_profile.php
│   │   │   ├── get_progress_reports.php
│   │   │   ├── get_progress_updates.php
│   │   │   ├── get_project_info.php
│   │   │   ├── get_project_progress.php
│   │   │   ├── get_received_designs.php
│   │   │   ├── get_room_improvement_analysis.php
│   │   │   ├── get_room_improvement_history.php
│   │   │   ├── get_sent_to_contractors.php
│   │   │   ├── initiate_alternative_payment.php
│   │   │   ├── initiate_custom_payment.php
│   │   │   ├── initiate_estimate_payment.php
│   │   │   ├── initiate_international_payment.php
│   │   │   ├── initiate_layout_payment.php
│   │   │   ├── initiate_smart_payment.php
│   │   │   ├── initiate_split_payment.php
│   │   │   ├── initiate_stage_payment.php
│   │   │   ├── initiate_technical_details_payment.php
│   │   │   ├── jwt_get_my_projects.php
│   │   │   ├── mark_notifications_read.php
│   │   │   ├── mark_photo_viewed.php
│   │   │   ├── process_split_payment.php
│   │   │   ├── respond_payment_request.php
│   │   │   ├── respond_to_custom_payment.php
│   │   │   ├── respond_to_estimate.php
│   │   │   ├── review_house_plan.php
│   │   │   ├── send_estimate_message.php
│   │   │   ├── send_house_plan_to_contractor.php
│   │   │   ├── send_to_contractor.php
│   │   │   ├── session_bridge.php
│   │   │   ├── start_construction.php
│   │   │   ├── submit_enhanced_request.php
│   │   │   ├── submit_request.php
│   │   │   ├── submit_validation_feedback.php
│   │   │   ├── test_create_notification.php
│   │   │   ├── test_image_validation.php
│   │   │   ├── test_profile.php
│   │   │   ├── update_design_selection.php
│   │   │   ├── update_profile.php
│   │   │   ├── upload_payment_receipt.php
│   │   │   ├── upload_payment_receipt.php.backup.2026-02-02-11-04-34
│   │   │   ├── upload_payment_receipt_enhanced.php
│   │   │   ├── verify_custom_payment.php
│   │   │   ├── verify_estimate_payment.php
│   │   │   ├── verify_layout_payment.php
│   │   │   ├── verify_split_payment.php
│   │   │   ├── verify_stage_payment.php
│   │   │   ├── verify_technical_details_payment.php
│   │   │   └── view_progress_report.php
│   │   ├── inspector
│   │   │   ├── create_enhanced_inspection_report.php
│   │   │   ├── create_inspection_report.php
│   │   │   ├── get_all_real_projects.php
│   │   │   ├── get_assigned_projects.php
│   │   │   ├── get_inspection_history.php
│   │   │   ├── get_inspection_reports.php
│   │   │   ├── get_project_details.php
│   │   │   ├── get_project_progress_details.php
│   │   │   ├── get_projects_simple.php
│   │   │   ├── get_projects_with_real_progress.php
│   │   │   ├── get_site_notes.php
│   │   │   ├── inspector_login.php
│   │   │   └── upload_inspection_photos.php
│   │   ├── ml
│   │   │   ├── data
│   │   │   ├── get_ai_evaluation_metrics.php
│   │   │   ├── get_evaluation_metrics.php
│   │   │   ├── get_project_analytics.php
│   │   │   ├── predict_construction_risks.php
│   │   │   ├── predict_construction_time.php
│   │   │   ├── retrain_models.php
│   │   │   ├── save_ai_prediction.php
│   │   │   ├── save_ai_predictions.php
│   │   │   ├── save_estimate_prediction.php
│   │   │   └── trigger_evaluation.php
│   │   ├── project
│   │   │   ├── get_project_overview.php
│   │   │   └── get_schedule_summary.php
│   │   ├── reviews
│   │   │   ├── get_reviews.php
│   │   │   └── post_review.php
│   │   ├── support
│   │   │   ├── admin_reply.php
│   │   │   ├── create_issue.php
│   │   │   └── get_issues.php
│   │   ├── test
│   │   │   └── test_payment_validation.php
│   │   ├── unified
│   │   │   ├── assign_architect.php
│   │   │   ├── contractor_engagement.php
│   │   │   ├── payment_system.php
│   │   │   └── project_state.php
│   │   ├── admin_verify.php
│   │   ├── budget_tracking.php
│   │   ├── debug_session.php
│   │   ├── forgot_password_request.php
│   │   ├── get_simple_receipts.php
│   │   ├── google_register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── ml_rules_engine.php
│   │   ├── ml_working.php
│   │   ├── register.php
│   │   ├── reset_password_update.php
│   │   ├── reset_password_verify.php
│   │   ├── schedule_tracking.php
│   │   ├── session_check.php
│   │   ├── test_ml.php
│   │   ├── test_proxy.php
│   │   ├── upload_reference_images.php
│   │   ├── upload_room_images.php
│   │   └── upload_site_images.php
│   ├── backend
│   │   └── models
│   │       └── architect_recommendation_model.pkl
│   ├── blockchain
│   │   ├── config
│   │   │   ├── .env.example
│   │   │   └── blockchain_config.php
│   │   ├── contracts
│   │   │   ├── TrustLayer.json
│   │   │   └── TrustLayer.sol
│   │   ├── database
│   │   │   ├── blockchain_schema.sql
│   │   │   └── blockchain_trust_schema.sql
│   │   ├── integration_patches
│   │   │   ├── admin_verification_patch.php
│   │   │   ├── contractor_verification_patch.php
│   │   │   ├── immutable_audit_integration.php
│   │   │   ├── payment_completion_patch.php
│   │   │   └── payment_initiation_patch.php
│   │   ├── services
│   │   │   └── TrustLayerService.php
│   │   ├── setup
│   │   │   ├── create_operation_queue_table.php
│   │   │   ├── create_stored_procedures.php
│   │   │   ├── deploy_blockchain_integration.php
│   │   │   ├── deployment_report_2026-01-28_09-58-08.json
│   │   │   └── integration_instructions.md
│   │   ├── BlockchainTrustLayer.php
│   │   ├── ImmutablePaymentAuditLedger.php
│   │   ├── PaymentAuditIntegrator.php
│   │   ├── PaymentBlockchainIntegrator.php
│   │   └── ReceiptVerificationBlockchainIntegrator.php
│   ├── config
│   │   ├── alternative_payment_config.php
│   │   ├── database.php
│   │   ├── db.php
│   │   ├── db_helper.php
│   │   ├── email_config.php
│   │   ├── international_payment_config.php
│   │   ├── payment_limits.php
│   │   ├── razorpay.php
│   │   ├── razorpay_config.php
│   │   └── split_payment_config.php
│   ├── database
│   │   ├── migrations
│   │   │   └── remove_materials_used_column.sql
│   │   ├── add_construction_notifications.sql
│   │   ├── add_international_payment_support.sql
│   │   ├── add_photo_fields_to_notifications.sql
│   │   ├── add_schedule_tracking_fields.sql
│   │   ├── add_technical_details_column.sql
│   │   ├── add_view_price_column.sql
│   │   ├── ai_evaluation_procedures.sql
│   │   ├── ai_model_retraining_schema.sql
│   │   ├── ai_model_versions_schema.sql
│   │   ├── ai_self_evaluation_schema.sql
│   │   ├── apply_jwt_schema.php
│   │   ├── check_jwt_tables.php
│   │   ├── contractor_document_management_schema.sql
│   │   ├── contractor_receipts_schema.sql
│   │   ├── create_alternative_payment_tables.sql
│   │   ├── create_construction_progress_tables.sql
│   │   ├── create_dashboard_tables.sql
│   │   ├── create_enhanced_progress_tables.sql
│   │   ├── create_house_plan_tables.sql
│   │   ├── create_inbox_messages_table.sql
│   │   ├── create_integrated_workflow_tables.sql
│   │   ├── create_progress_reports_table.sql
│   │   ├── create_room_improvement_table.sql
│   │   ├── create_split_payment_tables.sql
│   │   ├── create_stage_payment_tables.sql
│   │   ├── create_technical_details_payments.sql
│   │   ├── create_worker_management_tables.sql
│   │   ├── enhanced_inspection_schema.sql
│   │   ├── enhanced_room_templates.sql
│   │   ├── enhanced_stage_workflow_schema.sql
│   │   ├── jwt_tables.sql
│   │   ├── migrate_labour_tracking_enhancements.sql
│   │   ├── password_resets.sql
│   │   ├── prediction_copy_trigger.sql
│   │   ├── prediction_storage_fix.sql
│   │   ├── schedule_tracking_schema.sql
│   │   ├── schema_manager.php
│   │   ├── setup_room_improvement.php
│   │   ├── simple_receipts_schema.sql
│   │   └── site_inspector_schema.sql
│   ├── logs
│   ├── middleware
│   │   ├── AuthorizationMiddleware.php
│   │   └── JWTAuthMiddleware.php
│   ├── ml
│   │   ├── data
│   │   │   ├── cost_overrun_risk_dataset.csv
│   │   │   └── time_delay_risk_dataset.csv
│   │   ├── datasets
│   │   │   └── .gitkeep
│   │   ├── models
│   │   │   ├── cost_overrun_risk_model.pkl
│   │   │   ├── current_model.json
│   │   │   ├── model_metadata.json
│   │   │   └── time_delay_risk_model.pkl
│   │   ├── INTEGRATION_GUIDE.md
│   │   ├── README.md
│   │   ├── RETRAINING_SETUP.md
│   │   ├── construction_time_predictor.py
│   │   ├── current_model.json
│   │   ├── generate_training_dataset.py
│   │   ├── predict_api.py
│   │   ├── predict_risks_api.py
│   │   ├── requirements.txt
│   │   ├── retrain_models.py
│   │   ├── risk_prediction_pipeline.py
│   │   ├── risk_predictor.py
│   │   ├── risk_predictor_updated.py
│   │   ├── run_training.py
│   │   ├── setup.py
│   │   ├── test_api.py
│   │   ├── test_api_clean.py
│   │   └── test_complete_integration.py
│   ├── models
│   ├── scripts
│   │   ├── populate_existing_payments_audit.php
│   │   └── verify_populated_audit_data.php
│   ├── sql
│   │   └── create_concept_previews_table.sql
│   ├── utils
│   │   ├── AIServiceConnector.php
│   │   ├── BasicImageAnalyzer.php
│   │   ├── EnhancedRoomAnalyzer.php
│   │   ├── ImageFeatureExtractor.php
│   │   ├── JWTEndpointUpdater.php
│   │   ├── JWTManager.php
│   │   ├── PaymentRequestValidator.php
│   │   ├── RoomImageRelevanceValidator.php
│   │   ├── SentimentAnalyzer.php
│   │   ├── VisualAttributeMapper.php
│   │   ├── notification_helper.php
│   │   ├── send_mail.php
│   │   ├── smtp_mail.php
│   │   └── test_ai_service.php
│   ├── .htaccess
│   ├── README.md
│   ├── add_acknowledgment_column.php
│   ├── add_admin_verification_columns.php
│   ├── add_layout_image_column.php
│   ├── add_receipt_columns_to_stage_payments.php
│   ├── add_technical_details_column.php
│   ├── add_unlock_price_column.php
│   ├── apply_alternative_payment_schema.php
│   ├── apply_international_payment_schema.php
│   ├── apply_split_payment_schema.php
│   ├── architect_recommendation_api.py
│   ├── architect_recommendation_engine.py
│   ├── architect_request_solution.php
│   ├── architect_summary.php
│   ├── buildhub.sql
│   ├── check_250_payment.php
│   ├── check_acknowledgment_messages.php
│   ├── check_architects.py
│   ├── check_contractors.php
│   ├── check_cpu_table.php
│   ├── check_deletion_issue.php
│   ├── check_estimate_statuses.php
│   ├── check_homeowners.php
│   ├── check_house_plans_schema.php
│   ├── check_house_plans_with_images.php
│   ├── check_layout_requests_table.php
│   ├── check_mysql_connection.php
│   ├── check_payment_14_status.php
│   ├── check_payment_columns.php
│   ├── check_payment_completion.php
│   ├── check_payment_request_data.php
│   ├── check_payment_table.php
│   ├── check_plan_9_details.php
│   ├── check_project_37_data.php
│   ├── check_relationships.php
│   ├── check_replies_table.php
│   ├── check_support_table.php
│   ├── check_table_structure.php
│   ├── check_tables.php
│   ├── check_user_password.php
│   ├── check_users_table.php
│   ├── check_worker_types.php
│   ├── cleanup_all_test_users.php
│   ├── cleanup_test_architects.php
│   ├── cleanup_test_plans.php
│   ├── composer.json
│   ├── create_alternative_payment_tables_direct.php
│   ├── create_progress_table.php
│   ├── create_sample_acknowledged_projects.php
│   ├── create_sample_payment_data_with_receipts.php
│   ├── create_sample_payment_history.php
│   ├── create_sample_workers.php
│   ├── create_test_user_with_docs.php
│   ├── create_test_users.php
│   ├── debug_contractor_house_plans.php
│   ├── debug_delete_issue.php
│   ├── debug_ml.html
│   ├── debug_my_estimates.php
│   ├── debug_request_109.php
│   ├── debug_requests.php
│   ├── enable_production_email.php
│   ├── execute()
│   ├── find_estimate_37_owner.php
│   ├── fix_database_columns.sql
│   ├── fix_international_payment_schema.php
│   ├── fix_metadata_column.php
│   ├── fix_request_109.php
│   ├── getConnection()
│   ├── getMessage()
│   ├── migrate_total_cost_from_structured.php
│   ├── ml_api.py
│   ├── ml_rules_engine.py
│   ├── ml_simple.py
│   ├── prepare_for_new_upload.php
│   ├── requirements.txt
│   ├── reset_payment_for_testing.php
│   ├── rules_engine.py
│   ├── setup_construction_progress.php
│   ├── setup_contractor_tables.php
│   ├── setup_dashboard_tables.php
│   ├── setup_email.php
│   ├── setup_enhanced_labour_tracking.php
│   ├── setup_enhanced_progress_system.php
│   ├── setup_geo_photos.php
│   ├── setup_house_plans.php
│   ├── setup_house_plans_sqlite.php
│   ├── setup_inbox_messages.php
│   ├── setup_integrated_workflow.php
│   ├── setup_ml_engine.py
│   ├── setup_payment_table.php
│   ├── setup_phase_requirements.php
│   ├── setup_progress_reports.php
│   ├── setup_razorpay_keys.php
│   ├── setup_stage_payment_requests.php
│   ├── setup_stage_payments.php
│   ├── setup_stage_payments_simple.php
│   ├── setup_technical_details.php
│   ├── setup_technical_details_payments.php
│   ├── setup_worker_management.php
│   ├── setup_worker_management_simple.php
│   ├── show_tables.php
│   ├── simple_cleanup.php
│   ├── start_recommendation_engine.bat
│   ├── start_recommendation_engine.sh
│   ├── sync_payment_14.php
│   ├── test_10_lakhs_payment.php
│   ├── test_20_lakh_limit.php
│   ├── test_30lakh_output.json
│   ├── test_acknowledgment_api.php
│   ├── test_admin_api.php
│   ├── test_admin_direct.php
│   ├── test_admin_email.php
│   ├── test_admin_login_session.php
│   ├── test_admin_simple.php
│   ├── test_admin_support.php
│   ├── test_alternative_payment_api.php
│   ├── test_alternative_payment_simple.php
│   ├── test_api.html
│   ├── test_api.py
│   ├── test_api_endpoint.php
│   ├── test_api_flexible_payment.php
│   ├── test_architect_31_requests.php
│   ├── test_architect_after_fix.php
│   ├── test_architect_requests_direct.php
│   ├── test_backend.php
│   ├── test_button_text_prices.php
│   ├── test_complete_admin_flow.php
│   ├── test_complete_estimate_workflow.php
│   ├── test_complete_fix.php
│   ├── test_complete_upload_flow.php
│   ├── test_contractor_29_projects.php
│   ├── test_contractor_acknowledgment_messages.php
│   ├── test_contractor_inbox_debug.php
│   ├── test_contractor_layout_images.php
│   ├── test_contractor_projects_with_estimates.php
│   ├── test_delete_api_direct.php
│   ├── test_delete_request.php
│   ├── test_design_classification.py
│   ├── test_dynamic_pricing.php
│   ├── test_enhanced_labour_tracking.php
│   ├── test_estimates_simple.php
│   ├── test_estimation_api.php
│   ├── test_flexible_payment_amounts.php
│   ├── test_gd_extension.php
│   ├── test_geo_photo_system.php
│   ├── test_geo_photos_api.php
│   ├── test_geo_photos_setup.php
│   ├── test_homeowner_api_direct.php
│   ├── test_homeowner_query_logic.php
│   ├── test_house_plan_deletion.php
│   ├── test_house_plan_save.php
│   ├── test_house_plan_submission_workflow.php
│   ├── test_house_plan_visibility.php
│   ├── test_ml.py
│   ├── test_ml_from_dev.html
│   ├── test_my_estimates_fix.php
│   ├── test_my_estimates_flow.php
│   ├── test_notification_system.php
│   ├── test_payment_api_fresh.php
│   ├── test_payment_data.json
│   ├── test_payment_history_api.php
│   ├── test_payment_history_api_direct.php
│   ├── test_payment_history_direct.php
│   ├── test_payment_history_project_fix.php
│   ├── test_payment_history_with_receipts.php
│   ├── test_payment_initiation.php
│   ├── test_payment_query_direct.php
│   ├── test_payment_request_estimate_assignment.php
│   ├── test_payment_verification.php
│   ├── test_php_python.php
│   ├── test_progress_updates.php
│   ├── test_project_37_api.php
│   ├── test_project_creation_workflow.php
│   ├── test_razorpay_config.php
│   ├── test_razorpay_system.php
│   ├── test_real_email.php
│   ├── test_real_razorpay_keys.php
│   ├── test_real_razorpay_order.php
│   ├── test_receipt_upload_fix.php
│   ├── test_receipt_upload_system.php
│   ├── test_received_designs_api.php
│   ├── test_received_designs_api_direct.php
│   ├── test_received_designs_api_fixed.php
│   ├── test_received_designs_direct.php
│   ├── test_received_designs_simple.php
│   ├── test_received_designs_with_payments.php
│   ├── test_session_create.php
│   ├── test_session_debug.php
│   ├── test_shijin_thomas_project.php
│   ├── test_simple_ml.py
│   ├── test_simple_upload_check.php
│   ├── test_support_api.php
│   ├── test_upload_permissions.php
│   ├── test_verify_api_direct.php
│   ├── test_verify_payment_api.php
│   ├── test_verify_with_curl.php
│   ├── test_your_email.php
│   ├── update_payment_to_10_lakhs.php
│   ├── update_users_table.php
│   └── verify_razorpay_order.php
├── database
│   └── init
│       └── buildhub.sql
├── frontend
│   ├── buildhub.sql
│   ├── public
│   │   ├── images
│   │   │   ├── projects
│   │   │   │   ├── README.md
│   │   │   │   ├── commercial-1.jpg
│   │   │   │   ├── industrial-1.jpg
│   │   │   │   ├── renovation-1.jpg
│   │   │   │   └── residential-1.jpg
│   │   │   ├── services
│   │   │   │   └── README.md
│   │   │   ├── buildhub_image.jpg
│   │   │   ├── logo.png
│   │   │   ├── placeholder-design.svg
│   │   │   └── seal.jpg
│   │   └── vite.svg
│   ├── src
│   │   ├── components
│   │   │   ├── RequestAssistant
│   │   │   │   ├── RequestAssistant.jsx
│   │   │   │   ├── SimpleRequestAssistant.jsx
│   │   │   │   ├── index.js
│   │   │   │   ├── kb.json
│   │   │   │   ├── kb_enhanced.json
│   │   │   │   ├── kb_enhanced_backup.json
│   │   │   │   ├── matcher.js
│   │   │   │   ├── normalize.js
│   │   │   │   └── styles.css
│   │   │   ├── widgets
│   │   │   │   ├── DesignGallery.jsx
│   │   │   │   ├── NotificationSystem.jsx
│   │   │   │   └── ProjectTrackingWidgets.jsx
│   │   │   ├── wizard
│   │   │   │   ├── Stepper.jsx
│   │   │   │   └── WizardLayout.jsx
│   │   │   ├── AdminDashboard.jsx
│   │   │   ├── AdminInspectionReportsEnhancement.jsx
│   │   │   ├── AdminLogin.jsx
│   │   │   ├── AdminMaterialWizard.jsx
│   │   │   ├── AdminPaymentVerification.jsx
│   │   │   ├── ArchitectDashboard.jsx
│   │   │   ├── ArchitectDashboard_clean.jsx
│   │   │   ├── ArchitectDetailsModal.jsx
│   │   │   ├── ArchitectFullPageUpload.css
│   │   │   ├── ArchitectFullPageUpload.jsx
│   │   │   ├── ArchitectProfileButton.jsx
│   │   │   ├── ArchitectRecommendationEngine.jsx
│   │   │   ├── ArchitectRoute.jsx
│   │   │   ├── ArchitectSelection.css
│   │   │   ├── ArchitectSelection.jsx
│   │   │   ├── ArchitectSoftUI.jsx
│   │   │   ├── ArchitectUploadWizard.jsx
│   │   │   ├── ArchitecturalEnhancements.jsx
│   │   │   ├── AuthorizedRedirectURIs.jsx
│   │   │   ├── BlockchainAuditTrail.css
│   │   │   ├── BlockchainAuditTrail.jsx
│   │   │   ├── BlockchainTrustLayer.css
│   │   │   ├── BlockchainTrustLayer.jsx
│   │   │   ├── BuildHubSeal.jsx
│   │   │   ├── ConfirmModal.jsx
│   │   │   ├── ConstructionMetadata.jsx
│   │   │   ├── ConstructionProgressUpdate.jsx
│   │   │   ├── ConstructionProgressVisualization.jsx
│   │   │   ├── ConstructionTimeline.jsx
│   │   │   ├── ContractorConstructionTimeline.jsx
│   │   │   ├── ContractorDashboard.jsx
│   │   │   ├── ContractorDashboard_backup.jsx
│   │   │   ├── ContractorEstimateWizard.jsx
│   │   │   ├── ContractorPaymentManager.jsx
│   │   │   ├── ContractorPaymentVerification.jsx
│   │   │   ├── ContractorProfileButton.jsx
│   │   │   ├── ContractorRoute.jsx
│   │   │   ├── ContractorScheduleInput.css
│   │   │   ├── ContractorScheduleInput.jsx
│   │   │   ├── ContractorStageCompletion.jsx
│   │   │   ├── ContractorSubmittedReports.jsx
│   │   │   ├── CustomPaymentRequestForm.jsx
│   │   │   ├── Dashboard.css
│   │   │   ├── DocumentPreview.jsx
│   │   │   ├── EnhancedCanvasRenderer.jsx
│   │   │   ├── EnhancedConstructionProgressUpdate.jsx
│   │   │   ├── EnhancedMeasurements.jsx
│   │   │   ├── EnhancedProgressTimeline.jsx
│   │   │   ├── EnhancedProgressUpdate.jsx
│   │   │   ├── EnhancedRequestForm.jsx
│   │   │   ├── EnhancedSiteInspectionForm.jsx
│   │   │   ├── EnhancedStagePaymentRequest.jsx
│   │   │   ├── EstimationForm.jsx
│   │   │   ├── FastGeoPhotoCapture.jsx
│   │   │   ├── ForgotPassword.jsx
│   │   │   ├── GeoPhotoCapture.jsx
│   │   │   ├── GeoPhotoViewer.jsx
│   │   │   ├── GeometryConstraints.jsx
│   │   │   ├── HomeownerDashboard.jsx
│   │   │   ├── HomeownerDashboard.jsx.backup
│   │   │   ├── HomeownerDashboardTour.jsx
│   │   │   ├── HomeownerDashboard_fixed.jsx
│   │   │   ├── HomeownerDocumentViewer.jsx
│   │   │   ├── HomeownerPaymentDashboard.jsx
│   │   │   ├── HomeownerPaymentWithdrawals.jsx
│   │   │   ├── HomeownerProfile.jsx
│   │   │   ├── HomeownerProfileButton.jsx
│   │   │   ├── HomeownerProgressReports.jsx
│   │   │   ├── HomeownerProgressView.jsx
│   │   │   ├── HomeownerReceiptViewer.jsx
│   │   │   ├── HomeownerRequestWizard.jsx
│   │   │   ├── HomeownerRoute.jsx
│   │   │   ├── HomeownerScheduleView.css
│   │   │   ├── HomeownerScheduleView.jsx
│   │   │   ├── HousePlanDrawer.jsx
│   │   │   ├── HousePlanDrawerEnhanced.jsx
│   │   │   ├── HousePlanHelp.jsx
│   │   │   ├── HousePlanManager.jsx
│   │   │   ├── HousePlanTour.jsx
│   │   │   ├── HousePlanViewer.jsx
│   │   │   ├── HouseStyleSuggestions.css
│   │   │   ├── HouseStyleSuggestions.jsx
│   │   │   ├── ImageRelevanceValidator.css
│   │   │   ├── ImageRelevanceValidator.jsx
│   │   │   ├── InfoPopup.jsx
│   │   │   ├── InlineRoomImprovement.jsx
│   │   │   ├── InspectionHistory.jsx
│   │   │   ├── InspectionReportForm.jsx
│   │   │   ├── JWTLogin.jsx
│   │   │   ├── JWTProtectedRoute.jsx
│   │   │   ├── JWTUserProfile.jsx
│   │   │   ├── Login.jsx
│   │   │   ├── MLAnalyticsDashboard.css
│   │   │   ├── MLAnalyticsDashboard.jsx
│   │   │   ├── MLAnalyticsTab.css
│   │   │   ├── MLAnalyticsTab.jsx
│   │   │   ├── MessageCenter.jsx
│   │   │   ├── Navbar.jsx
│   │   │   ├── NavigationWrapper.jsx
│   │   │   ├── NeatJsonCard.css
│   │   │   ├── NeatJsonCard.jsx
│   │   │   ├── NotificationBadge.jsx
│   │   │   ├── NotificationToast.jsx
│   │   │   ├── OCRVerificationModal.jsx
│   │   │   ├── PageLoader.css
│   │   │   ├── PageLoader.jsx
│   │   │   ├── PaymentHistory.jsx
│   │   │   ├── PaymentHistoryDebug.jsx
│   │   │   ├── PaymentMethodSelector.jsx
│   │   │   ├── PaymentReceiptUpload.jsx
│   │   │   ├── PaymentReceiptViewer.jsx
│   │   │   ├── PaymentRequestTab.jsx
│   │   │   ├── PaymentValidationDisplay.css
│   │   │   ├── PaymentValidationDisplay.jsx
│   │   │   ├── ProfileButton.css
│   │   │   ├── ProfileButton.jsx
│   │   │   ├── ProfileButtonExample.jsx
│   │   │   ├── ProfileButtonIntegration.md
│   │   │   ├── ProfileButtonTest.jsx
│   │   │   ├── ProfilePopup.css
│   │   │   ├── ProfilePopup.jsx
│   │   │   ├── ProgressReportGenerator.jsx
│   │   │   ├── ProgressTimeline.jsx
│   │   │   ├── ProjectDetailsModal.jsx
│   │   │   ├── ProjectInfoCard.jsx
│   │   │   ├── ProtectedAdminRoute.jsx
│   │   │   ├── Register.jsx
│   │   │   ├── RequirementsDisplay.jsx
│   │   │   ├── ResetPassword.jsx
│   │   │   ├── RiskAssessmentPreview.jsx
│   │   │   ├── RoomImprovementAssistant.jsx
│   │   │   ├── ScheduleTrackingPanel.jsx
│   │   │   ├── SearchableDropdown.jsx
│   │   │   ├── SimpleCameraTest.jsx
│   │   │   ├── SimplePaymentRequestForm.jsx
│   │   │   ├── SimpleReceiptManager.jsx
│   │   │   ├── SimpleReceiptUpload.jsx
│   │   │   ├── SimpleReceiptViewer.jsx
│   │   │   ├── SimpleRequestForm.jsx
│   │   │   ├── SiteInspectionDashboard.jsx
│   │   │   ├── SiteInspectorDashboard.jsx
│   │   │   ├── SiteInspectorLogin.jsx
│   │   │   ├── StageCompletionButton.jsx
│   │   │   ├── StageDocumentManager.jsx
│   │   │   ├── StagePaymentRequest.jsx
│   │   │   ├── StagePaymentWithdrawals.jsx
│   │   │   ├── StylishProfile.css
│   │   │   ├── StylishProfile.jsx
│   │   │   ├── TechnicalDetailsDisplay.jsx
│   │   │   ├── TechnicalDetailsForm.jsx
│   │   │   ├── TechnicalDetailsModal.jsx
│   │   │   ├── TimelinePrediction.css
│   │   │   ├── TimelinePrediction.jsx
│   │   │   ├── ToastProvider.jsx
│   │   │   ├── TourGuide.jsx
│   │   │   ├── TourGuideDemo.jsx
│   │   │   ├── ValidationTest.jsx
│   │   │   ├── WallSystem.jsx
│   │   │   └── WidgetColors.css
│   │   ├── config
│   │   ├── data
│   │   │   ├── indianCities.js
│   │   │   └── locationData.js
│   │   ├── hooks
│   │   │   └── useNotifications.js
│   │   ├── styles
│   │   │   ├── AdminDashboard.css
│   │   │   ├── AdminLogin.css
│   │   │   ├── AdminPaymentVerification.css
│   │   │   ├── AdminSupport.css
│   │   │   ├── ArchitectDashboard.css
│   │   │   ├── ArchitectRecommendation.css
│   │   │   ├── ArchitectSelection.css
│   │   │   ├── ArchitecturalEnhancements.css
│   │   │   ├── BlueGlassTheme.css
│   │   │   ├── BuildHubSeal.css
│   │   │   ├── ConfirmModal.css
│   │   │   ├── ConstructionProgress.css
│   │   │   ├── ConstructionProgressVisualization.css
│   │   │   ├── ConstructionTimeline.css
│   │   │   ├── ContractorDashboard.css
│   │   │   ├── ContractorPaymentManager.css
│   │   │   ├── ContractorPaymentVerification.css
│   │   │   ├── ContractorStageCompletion.css
│   │   │   ├── ContractorSubmittedReports.css
│   │   │   ├── CustomPaymentRequestForm.css
│   │   │   ├── DocumentPreview.css
│   │   │   ├── EnhancedConstructionProgress.css
│   │   │   ├── EnhancedEstimationForm.css
│   │   │   ├── EnhancedInspectionForm.css
│   │   │   ├── EnhancedProgress.css
│   │   │   ├── EnhancedProgressTimeline.css
│   │   │   ├── EnhancedRequestForm.css
│   │   │   ├── EnhancedSiteInspectionForm.css
│   │   │   ├── EstimationForm.css
│   │   │   ├── FamilyNeeds.css
│   │   │   ├── FamilyNeedsSection.css
│   │   │   ├── FullPageForm.css
│   │   │   ├── GeoPhotoCapture.css
│   │   │   ├── GeoPhotoViewer.css
│   │   │   ├── HeaderProfile.css
│   │   │   ├── HomeownerDashboard.css
│   │   │   ├── HomeownerDocumentViewer.css
│   │   │   ├── HomeownerPaymentDashboard.css
│   │   │   ├── HomeownerPaymentWithdrawals.css
│   │   │   ├── HomeownerProfile.css
│   │   │   ├── HomeownerProgress.css
│   │   │   ├── HomeownerProgressReports.css
│   │   │   ├── HomeownerReceiptViewer.css
│   │   │   ├── HousePlanDrawer.css
│   │   │   ├── HousePlanHelp.css
│   │   │   ├── HousePlanManager.css
│   │   │   ├── HousePlanTour.css
│   │   │   ├── HousePlanViewer.css
│   │   │   ├── ImageErrorHandling.css
│   │   │   ├── InboxEstimationForm.css
│   │   │   ├── InlineRoomImprovement.css
│   │   │   ├── InspectionHistory.css
│   │   │   ├── InspectionReportForm.css
│   │   │   ├── InteractiveOptionsFix.css
│   │   │   ├── Login.css
│   │   │   ├── MessageCenter.css
│   │   │   ├── Navbar.css
│   │   │   ├── NoInternalScroll.css
│   │   │   ├── NotificationToast.css
│   │   │   ├── OCRVerificationModal.css
│   │   │   ├── PaymentDetailsModal.css
│   │   │   ├── PaymentHistory.css
│   │   │   ├── PaymentMethodSelector.css
│   │   │   ├── PaymentReceiptUpload.css
│   │   │   ├── PaymentRequestTab.css
│   │   │   ├── ProfessionalReportForm.css
│   │   │   ├── ProgressReportGenerator.css
│   │   │   ├── ProgressReportGeneratorHistory.css
│   │   │   ├── ProgressTimeline.css
│   │   │   ├── ProjectDetailsModal.css
│   │   │   ├── ProjectInfoCard.css
│   │   │   ├── Register.css
│   │   │   ├── ReviewSection.css
│   │   │   ├── RiskAssessmentPreview.css
│   │   │   ├── RoomImprovementAssistant.css
│   │   │   ├── SearchableDropdown.css
│   │   │   ├── SimplePaymentRequestForm.css
│   │   │   ├── SimplePaymentRequestForm_Unified.css
│   │   │   ├── SimpleReceiptManager.css
│   │   │   ├── SimpleReceiptUpload.css
│   │   │   ├── SimpleReceiptViewer.css
│   │   │   ├── SiteInspectionDashboard.css
│   │   │   ├── SiteInspectorDashboard.css
│   │   │   ├── SiteInspectorLogin.css
│   │   │   ├── SoftSidebar.css
│   │   │   ├── StageDocumentManager.css
│   │   │   ├── StagePaymentRequest.css
│   │   │   ├── StagePaymentWithdrawals.css
│   │   │   ├── StageProgression.css
│   │   │   ├── SupportSystem.css
│   │   │   ├── TechnicalDetailsForm.css
│   │   │   ├── TechnicalDetailsModal.css
│   │   │   ├── TourGuide.css
│   │   │   ├── Widgets.css
│   │   │   ├── Wizard.css
│   │   │   ├── toast.css
│   │   │   └── variables.css
│   │   ├── utils
│   │   │   ├── alternativePaymentHandler.js
│   │   │   ├── designRulesEngine.js
│   │   │   ├── internationalPaymentHandler.js
│   │   │   ├── jwtAuth.js
│   │   │   ├── modalUtils.js
│   │   │   ├── progressValidation.js
│   │   │   ├── session.js
│   │   │   ├── splitPaymentHandler.js
│   │   │   ├── stageProgressionLogic.js
│   │   │   └── status.js
│   │   ├── App.css
│   │   ├── App.jsx
│   │   ├── index.css
│   │   └── main.jsx
│   ├── styles
│   │   ├── ArchitectDashboard.css
│   │   ├── ArchitectSoftUI.css
│   │   ├── ContractorDashboard.css
│   │   └── HomeownerDashboard.css
│   ├── tests
│   │   ├── architect-dashboard.spec.ts
│   │   ├── homeowner-dashboard.spec.ts
│   │   ├── homepage.spec.ts
│   │   └── login.spec.ts
│   ├── README.md
│   ├── demo_payment_handler.js
│   ├── index.html
│   ├── package-lock.json
│   ├── package.json
│   ├── playwright.config.ts
│   └── vite.config.js
├── models
├── tests
│   ├── blockchain
│   │   └── blockchain_integration_test.html
│   ├── demos
│   │   ├── README.md
│   │   ├── admin_payment_verification_complete_test.html
│   │   ├── alternative_payment_test.html
│   │   ├── architect_pending_requests_test.html
│   │   ├── auto_project_name_estimation_form_test.html
│   │   ├── auto_save_draft_estimation_form_test.html
│   │   ├── bank_transfer_payment_test.html
│   │   ├── chatbot_test.html
│   │   ├── complete_bank_transfer_test.html
│   │   ├── complete_estimate_to_project_workflow_test.html
│   │   ├── complete_estimation_workflow_test.html
│   │   ├── complete_payment_system_test.html
│   │   ├── contractor_acknowledgment_message_test.html
│   │   ├── contractor_acknowledgment_workflow_test.html
│   │   ├── contractor_estimation_form_test.html
│   │   ├── contractor_inbox_layout_images_test.html
│   │   ├── contractor_layout_images_test.html
│   │   ├── cost_request_cards_test.html
│   │   ├── download_functionality_test.html
│   │   ├── enhanced_estimation_form_ui_test.html
│   │   ├── enhanced_inbox_estimation_form_test.html
│   │   ├── enhanced_payment_request_system_test.html
│   │   ├── enhanced_progress_worker_selection_fix.html
│   │   ├── enhanced_progress_worker_test.html
│   │   ├── enhanced_technical_details_modal_test.html
│   │   ├── fixed_razorpay_test.html
│   │   ├── fixed_user_journey_test.html
│   │   ├── flexible_payment_amounts_test.html
│   │   ├── geo_photo_coordinate_test.html
│   │   ├── homeowner_payment_requests_test.html
│   │   ├── homeowner_plan_visibility_test.html
│   │   ├── house_plan_designer_demo.html
│   │   ├── improved-technical-form.html
│   │   ├── inbox_estimation_button_test.html
│   │   ├── inbox_messages_table_fix_test.html
│   │   ├── integrated-technical-form.html
│   │   ├── integrated_workflow_demo.html
│   │   ├── international_payment_test.html
│   │   ├── layout-card-ui.html
│   │   ├── modal_positioning_fix_test.html
│   │   ├── multi_floor_house_plan_test.html
│   │   ├── my_estimates_display_test.html
│   │   ├── my_estimates_fix_test.html
│   │   ├── notification_test.html
│   │   ├── payment_10_lakhs_test.html
│   │   ├── payment_20_lakhs_test.html
│   │   ├── payment_amount_mismatch_fix_test.html
│   │   ├── payment_details_receipt_upload_test.html
│   │   ├── payment_history_api_test.html
│   │   ├── payment_history_debug_fix_test.html
│   │   ├── payment_history_fix_complete_test.html
│   │   ├── payment_history_section_test.html
│   │   ├── payment_history_test.html
│   │   ├── payment_history_with_receipts_fixed_test.html
│   │   ├── payment_history_with_receipts_test.html
│   │   ├── payment_modal_positioning_fix_test.html
│   │   ├── payment_receipt_upload_test.html
│   │   ├── payment_request_cost_in_project_info_test.html
│   │   ├── payment_request_form_improved_layout_test.html
│   │   ├── payment_request_form_unified_design_test.html
│   │   ├── payment_system_workflow_test.html
│   │   ├── project_creation_workflow_test.html
│   │   ├── project_info_neat_ui_test.html
│   │   ├── real_projects_payment_request_test.html
│   │   ├── real_razorpay_payment_test.html
│   │   ├── receipt_upload_fix_test.html
│   │   ├── receipt_upload_header_buttons_test.html
│   │   ├── receipt_upload_modal_positioning_fix_test.html
│   │   ├── received_designs_with_house_plans_test.html
│   │   ├── simple-technical-form.html
│   │   ├── simple_payment_request_form_test.html
│   │   ├── smooth_scrolling_estimation_form_test.html
│   │   ├── split_payment_test.html
│   │   ├── stage_payment_withdrawals_test.html
│   │   ├── technical_details_modal_fix_test.html
│   │   ├── technical_details_payment_complete_test.html
│   │   ├── technical_details_payment_test.html
│   │   ├── test-form.html
│   │   ├── test_request_deletion.html
│   │   ├── upload_design_database_fix_test.html
│   │   ├── upload_design_functionality_test.html
│   │   ├── visual_design_download_test.html
│   │   ├── worker_selection_demo.html
│   │   └── worker_selection_validation_test.html
│   ├── e2e
│   │   ├── helpers
│   │   │   └── auth.js
│   │   ├── CONSTRUCTION_PROGRESS_TESTS.md
│   │   ├── README_PROGRESS_TESTS.md
│   │   ├── ai-features.spec.js
│   │   ├── architect-design-editor.spec.js
│   │   ├── construction-progress-detailed.spec.js
│   │   ├── construction-progress-navigation.spec.js
│   │   ├── construction-progress.spec.js
│   │   ├── contractor-dashboard.spec.js
│   │   ├── homeowner-dashboard.spec.js
│   │   ├── inspector-dashboard.spec.js
│   │   ├── payment-flow.spec.js
│   │   └── simple-login-test.spec.js
│   ├── fixtures
│   │   └── sample-receipt.pdf
│   ├── complete_image_flow_test.html
│   ├── complete_upload_flow_test.html
│   ├── construction_progress_visualization_test.html
│   ├── enhanced_contractor_modal_test.html
│   ├── expanded_contractor_modal_test.html
│   ├── file_upload_test.html
│   ├── house_plan_contractor_test.html
│   ├── image_error_handling_test.html
│   ├── simple_contractor_modal_test.html
│   └── spacious_contractor_modal_test.html
├── .gitattributes
├── .gitignore
├── 500_ERROR_FIX_COMPLETE.md
├── ACTION_PLAN_TO_FIX.md
├── ADMIN_INSPECTION_REPORTS_IMPLEMENTATION_COMPLETE.md
├── ADMIN_PAYMENT_VERIFICATION_SYSTEM_IMPLEMENTED.md
├── AI_ENHANCEMENT_README.md
├── AI_EVALUATION_TESTING_GUIDE.md
├── AI_RISK_ASSESSMENT_ANALYSIS.md
├── AI_SELF_EVALUATION_FRAMEWORK.md
├── AI_SELF_EVALUATION_IMPLEMENTATION_SUMMARY.md
├── AI_SELF_EVALUATION_QUICK_REFERENCE.md
├── AI_SELF_EVALUATION_QUICK_START.md
├── AI_SYSTEM_AUDIT_SUMMARY.md
├── AI_SYSTEM_COMPLETE_EXPLANATION.md
├── AI_SYSTEM_COMPLETE_WORKFLOW_VISUAL.md
├── AI_SYSTEM_DOCUMENTATION_INDEX.md
├── AI_SYSTEM_EXECUTION_FLOW.md
├── AI_SYSTEM_INTEGRATION_COMPLETE.md
├── AI_SYSTEM_QUICK_REFERENCE.md
├── AI_SYSTEM_SIMPLE_SUMMARY.md
├── AI_SYSTEM_VISUAL_GUIDE.md
├── AI_SYSTEM_VISUAL_WORKFLOW.md
├── ALL_ISSUES_FIXED.md
├── ALTERNATIVE_PAYMENT_METHODS_IMPLEMENTED.md
├── API_COMPATIBILITY_FIX.md
├── ARCHITECTURAL_ENHANCEMENTS_README.md
├── ARCHITECT_DESIGN_EDITOR_TEST_GUIDE.md
├── ASYNC_AI_IMAGE_GENERATION_FIX.md
├── ASYNC_CONCEPT_GENERATION_IMPLEMENTATION.md
├── AUTO_NOTE_REMOVAL_COMPLETE.md
├── AVAILABLE_PROJECTS_SECTION_REMOVED.md
├── BANK_TRANSFER_PAYMENT_IMPLEMENTED.md
├── BEFORE_AFTER_COMPARISON.md
├── BILLS_RECEIPTS_CARDINALITY_FIX.md
├── BLOCKCHAIN_DEMO_GUIDE_FOR_PROJECT_GUIDE.md
├── BLOCKCHAIN_INTEGRATION_COMPLETE.md
├── BLOCKCHAIN_TRUST_LAYER_README.md
├── BUDGET_ALIGNMENT_FIX_COMPLETE.md
├── CAMERA_BLACK_SCREEN_FIX.md
├── CLEAR_CACHE_SEE_REAL_DATA.md
├── COMPLETED_PROJECTS_SECTION_ADDED.md
├── COMPLETED_PROJECT_OVERRUN_DISPLAY.md
├── CONSTRUCTION_AI_INTEGRATION_STATUS.md
├── CONSTRUCTION_AI_SYSTEM_ARCHITECTURE_AUDIT.md
├── CONSTRUCTION_AI_SYSTEM_AUDIT_REPORT.md
├── CONSTRUCTION_AI_SYSTEM_COMPLETE_AUDIT.md
├── CONTRACTOR_DAILY_REPORTS_FIX_COMPLETE.md
├── CONTRACTOR_DOCUMENT_MANAGEMENT_IMPLEMENTATION.md
├── CONTRACTOR_RECEIPT_UPLOAD_FIX_COMPLETE.md
├── CONTRACTOR_UPLOAD_FIX_COMPLETE.md
├── CORS_TROUBLESHOOTING_GUIDE.md
├── COST_REQUEST_CARDS_IMPLEMENTED.md
├── COST_TIME_OVERRUN_COMPLETE_WORKFLOW.md
├── COST_TIME_OVERRUN_SYSTEM_STATUS.md
├── COST_TIME_OVERRUN_TESTING_GUIDE.md
├── COST_TIME_OVERRUN_VISUAL_SUMMARY.md
├── CUSTOM_PAYMENT_FIX_COMPLETE.md
├── CUSTOM_PAYMENT_FLOW_FIX_COMPLETE.md
├── CUSTOM_PAYMENT_REQUESTS_HOMEOWNER_DASHBOARD_FIX_COMPLETE.md
├── CUSTOM_PAYMENT_REQUESTS_IMPLEMENTATION.md
├── CUSTOM_REQUEST_REVIEW_AND_HOUSE_STYLE_FIXES.md
├── DAILY_PROGRESS_CORRECTION_COMPLETE.md
├── DAILY_PROGRESS_SUBMISSION_FIX_COMPLETE.md
├── DAILY_REPORT_STAGE_SELECTION_FIX.md
├── DEPLOY_AI_FIX_NOW.md
├── DESIGN_DELETION_FIX_COMPLETE.md
├── DESIGN_EDITOR_SYSTEM_ANALYSIS.md
├── DIAGNOSIS_REPORT.md
├── DISTRICT_SELECTION_IMPLEMENTED.md
├── DOWNLOAD_FUNCTIONALITY_IMPLEMENTATION_COMPLETE.md
├── DYNAMIC_UPDATE_COUNTS_FIX_COMPLETE.md
├── ENHANCED_AI_CAPABILITIES_IMPLEMENTATION.md
├── ENHANCED_AUTO_POPULATION_SYSTEM_IMPLEMENTED.md
├── ENHANCED_INSPECTION_SYSTEM_IMPLEMENTATION.md
├── ENHANCED_PAYMENT_REQUEST_SYSTEM_IMPLEMENTED.md
├── ENHANCED_ROOM_IMPROVEMENT_SYSTEM.md
├── ENHANCED_SITE_INSPECTION_SYSTEM_IMPLEMENTATION.md
├── ENHANCED_TIMELINE_IMPLEMENTATION_COMPLETE.md
├── ENV_SETUP.md
├── ESTIMATE_COST_RETRIEVAL_FIX.md
├── FINAL_PROGRESS_FIX_TEST.html
├── FINAL_SYSTEM_SUMMARY.md
├── FINAL_SYSTEM_VERIFICATION_REPORT.md
├── FIX_ML_ANALYTICS_ERROR.md
├── FIX_SYSTEM_NOW.md
├── FIX_TABLE_ERROR.md
├── FRONTEND_INTEGRATION_FIX_COMPLETE.md
├── FRONTEND_PROGRESS_FIX_COMPLETE.md
├── HASHING_EXPLANATION.md
├── HOMEOWNER_CUSTOM_REQUEST_FORM_FIELDS.md
├── HOMEOWNER_DASHBOARD_BLACK_SCREEN_FIX.md
├── HOMEOWNER_DASHBOARD_IMAGE_FIX.md
├── HOMEOWNER_RECEIPT_DISPLAY_FIX_COMPLETE.md
├── HOUSE_PLAN_LAYOUT_SUBMISSION_ENHANCEMENT.md
├── HOUSE_PLAN_SAVE_PERSISTENCE_FIX.md
├── HOUSE_PLAN_SUBMISSION_VISIBILITY_FIX.md
├── IMMUTABLE_PAYMENT_AUDIT_SYSTEM_IMPLEMENTATION.md
├── IMPLEMENTATION_CHECKLIST.md
├── INSPECTION_REPORTS_DEBUGGING_SUMMARY.md
├── INSPECTION_REPORTS_IMPLEMENTATION_COMPLETE.md
├── INSPECTION_REPORTS_INTEGRATED_INTO_CONSTRUCTION_PROGRESS.md
├── INSPECTOR_API_AUTHENTICATION_FIX_COMPLETE.md
├── INTEGRATE_ML_ANALYTICS_NOW.md
├── JSON_SORT_KEYS_FIX_SUMMARY.md
├── JWT_IMPLEMENTATION_GUIDE.md
├── JWT_IMPLEMENTATION_SUMMARY.md
├── LAYOUT_IMAGE_DISPLAY_ISSUE_RESOLVED.md
├── LOCATION_SYSTEM_STATUS.md
├── MATERIALS_USED_FIELD_REMOVAL.md
├── ML_ANALYTICS_DASHBOARD_IMPLEMENTATION.md
├── ML_ANALYTICS_IMPLEMENTATION_COMPLETE.md
├── ML_ANALYTICS_INTEGRATION_COMPLETE.md
├── ML_ANALYTICS_QUICK_REFERENCE.md
├── ML_ANALYTICS_SETUP_COMPLETE.md
├── ML_ANALYTICS_VISUAL_SUMMARY.md
├── ML_IMPLEMENTATION_SUMMARY.md
├── ML_RETRAINING_INTEGRATION_GUIDE.md
├── ML_RETRAINING_PIPELINE_COMPLETE.md
├── ML_RETRAINING_PIPELINE_GUIDE.md
├── ML_RETRAINING_QUICK_START.md
├── ML_RETRAINING_TESTING_GUIDE.md
├── ML_RETRAINING_VISUAL_GUIDE.md
├── MY_ESTIMATES_FIX_IMPLEMENTED.md
├── OVERRUN_DISPLAY_SUMMARY.md
├── PREDICTION_STORAGE_FIX_COMPLETE.md
├── PROGRESS_DATA_FIX_COMPLETE.md
├── PROGRESS_FIXED_FINAL.md
├── PROGRESS_REPORTS_COMPLETE_FIX_SUMMARY.md
├── PROGRESS_REPORTS_FIX_COMPLETE.md
├── PROJECT_37_COMPLETION_FINAL_STATUS.md
├── PROJECT_37_COMPLETION_GUIDE.md
├── PROJECT_37_COMPLETION_SUCCESS.md
├── PROJECT_37_FINAL_STAGE_COMPLETE.md
├── PROJECT_3_COMPLETION_SUCCESS.md
├── PROJECT_COMPLETION_FINAL_SUMMARY.md
├── PROJECT_INFO_UPDATE_FIX.md
├── PROJECT_SELECTION_FIX_SUMMARY.md
├── PROJECT_SPECIFIC_INSPECTION_REPORTS_COMPLETE.md
├── QUICK_ML_ANALYTICS_INTEGRATION_GUIDE.md
├── QUICK_START_AI_TESTING.md
├── QUICK_START_GUIDE.md
├── QUICK_TEST_GUIDE.md
├── REACT_OBJECT_RENDERING_FIX.md
├── README_PLAYWRIGHT.md
├── REAL_DATA_ML_ANALYTICS_READY.md
├── REAL_PROGRESS_IMPLEMENTATION_COMPLETE.md
├── REAL_PROJECTS_SYSTEM_COMPLETE.md
├── RECEIPT_STORAGE_GUIDE.md
├── RECEIPT_UPLOAD_ISSUE_FIXED.md
├── RECEIPT_VERIFICATION_ISSUE_COMPLETELY_FIXED.md
├── RESEARCH_SEMINAR_DETAILED_DOCUMENTATION.md
├── RISK_BLOCKING_CHECKLIST.md
├── RISK_BLOCKING_CODE_SNIPPET.md
├── RISK_BLOCKING_SUMMARY.md
├── RISK_BLOCKING_VALIDATION_IMPLEMENTATION.md
├── RISK_BLOCKING_VISUAL_GUIDE.md
├── ROOM_IMPROVEMENT_API_ERROR_FIX.md
├── ROOM_IMPROVEMENT_ASSISTANT_IMPLEMENTATION.md
├── ROOM_IMPROVEMENT_COMPLETE_SOLUTION.md
├── SCHEDULE_TRACKING_IMPLEMENTATION.md
├── SCHEDULE_TRACKING_QUICK_REFERENCE.md
├── SCHEDULE_TRACKING_QUICK_START.md
├── SCHEDULE_TRACKING_README.md
├── SCHEDULE_TRACKING_SUMMARY.md
├── SCHEDULE_TRACKING_VISUAL_GUIDE.md
├── SEMESTER_ENHANCEMENTS_TECHNICAL_REPORT.md
├── SEMINAR_COST_TIME_OVERRUN_DETAILS.md
├── SESSION_VARIABLE_CONFLICT_FIXED.md
├── SHIJIN_THOMAS_PROJECT_TROUBLESHOOTING.md
├── SIDE_PANEL_LAYOUT_IMPLEMENTATION.md
├── SIMPLE_RECEIPT_SYSTEM_INTEGRATION_GUIDE.md
├── SITE_DETAILS_DROPDOWN_FIX_COMPLETE.md
├── SITE_DETAILS_ESTIMATION_ENHANCEMENT_COMPLETE.md
├── SITE_DETAILS_ESTIMATION_FORM_IMPLEMENTATION_COMPLETE.md
├── SITE_DETAILS_TROUBLESHOOTING_GUIDE.md
├── SITE_INSPECTION_401_FIX_COMPLETE.md
├── SITE_INSPECTION_FORM_FIELDS_COMPREHENSIVE_GUIDE.md
├── SITE_INSPECTION_ISSUE_RESOLVED.md
├── SITE_INSPECTION_PROGRESS_FIX_COMPLETE.md
├── SITE_INSPECTION_REPORTS_INTEGRATION_COMPLETE.md
├── SITE_INSPECTION_SYSTEM_IMPLEMENTATION.md
├── SITE_INSPECTOR_500_ERROR_COMPLETE_FIX.md
├── SITE_INSPECTOR_DASHBOARD_README.md
├── SPLIT_PAYMENT_SYSTEM_IMPLEMENTED.md
├── SQL_PARAMETER_MISMATCH_FIXED.md
├── STAGE_DOCUMENTS_SQL_FIX_COMPLETE.md
├── STAGE_PAYMENT_REQUEST_SUBMISSION_FIX_COMPLETE.md
├── STAGE_PAYMENT_WITHDRAWALS_IMPLEMENTED.md
├── STAGE_PROGRESS_DISPLAY_FIX_COMPLETE.md
├── STAGE_VALIDATION_FIX_COMPLETE.md
├── START_HERE.md
├── START_HERE_FIXED.md
├── START_HERE_ML_ANALYTICS.md
├── SYNTAX_ERROR_FIXED.md
├── SYSTEM_NOW_OPERATIONAL.md
├── SYSTEM_VERIFICATION_CHECKLIST.md
├── SYSTEM_VERIFICATION_REPORT.md
├── TASK_4_PROFESSIONAL_FORM_UI_COMPLETION.md
├── TASK_6_PROJECT_INFO_FIELDS_FIX_COMPLETE.md
├── TECHNICAL_DETAILS_PAYMENT_FUNCTION_FIX.md
├── TECHNICAL_DETAILS_PAYMENT_SYSTEM_FIXED.md
├── TIMEOUT_ISSUE_FIXED_ASYNC_REAL_AI.md
├── TOTAL_COST_FROM_STRUCTURED_JSON_FIX.md
├── UNIFIED_DESIGN_IMPLEMENTATION_SUMMARY.md
├── USER_JOURNEY_FIXES.md
├── VIEW_REAL_DATA_NOW.md
├── VIEW_TIMELINE_ALL_PROJECTS_IMPLEMENTED.md
├── VIEW_TIMELINE_BUDGET_DISPLAY_IMPLEMENTED.md
├── add_final_stage_project_37.php
├── add_overrun_columns.php
├── ai_service_dashboard.html
├── apply_ai_model_versions_migration.php
├── apply_ai_model_versions_schema.php
├── apply_ai_schema_fixed.php
├── apply_ai_self_evaluation_migration.php
├── apply_complete_ai_schema.php
├── apply_document_management_schema.php
├── apply_enhanced_inspection_schema.php
├── apply_prediction_copy_trigger.php
├── apply_schedule_tracking_migration.php
├── apply_schema_now.php
├── apply_triggers_procedures.php
├── apply_weekly_progress_schema.php
├── assign_contractor_32_to_project.php
├── assign_inspector_to_active_projects.php
├── assign_project_3_to_inspector.php
├── check-server.bat
├── check_actual_schema.php
├── check_all_projects.php
├── check_amal_samuel_receipt.php
├── check_audit_table.php
├── check_available_tables.php
├── check_available_tables_simple.php
├── check_blockchain_current_status.php
├── check_cls_columns.php
├── check_completed_projects.php
├── check_construction_projects_columns.php
├── check_construction_projects_schema.php
├── check_contractor_assignments.php
├── check_contractor_ids.php
├── check_custom_payment_flow.php
├── check_custom_payment_status.php
├── check_custom_payments.php
├── check_daily_progress_columns.php
├── check_daily_progress_data.php
├── check_daily_progress_schema.php
├── check_daily_progress_table.php
├── check_daily_schema.php
├── check_database_schema.php
├── check_database_tables.php
├── check_database_tables_and_project.php
├── check_db_tables.php
├── check_documents_schema.php
├── check_documents_table_structure.php
├── check_estimate_schema.php
├── check_estimates_table.php
├── check_existing_progress.php
├── check_existing_receipts.php
├── check_final_stage_issue.php
├── check_homeowner_exists.php
├── check_homeowner_payments.php
├── check_homeowner_reports_data.php
├── check_inspection_reports_table.php
├── check_inspection_schema.php
├── check_inspection_table.php
├── check_inspection_table_structure.php
├── check_inspector_assignments.php
├── check_metrics_table.php
├── check_mysql_database.php
├── check_mysql_tables.php
├── check_payment_10.php
├── check_payment_16_details.php
├── check_payment_16_receipt_data.php
├── check_payment_columns.php
├── check_payment_data.php
├── check_payment_requests.php
├── check_payment_requests_for_testing.php
├── check_payment_schema.php
├── check_payment_tables_structure.php
├── check_progress_data.php
├── check_progress_data_simple.php
├── check_progress_reports.php
├── check_progress_reports_table.php
├── check_progress_schema.php
├── check_progress_table_structures.php
├── check_project_111.php
├── check_project_3.php
├── check_project_37.php
├── check_project_37_exists.php
├── check_project_37_mysql.php
├── check_project_37_status.php
├── check_project_38_actual.php
├── check_project_completion.php
├── check_project_id_mismatch.php
├── check_project_names.php
├── check_project_specific_data.php
├── check_projects.php
├── check_projects_table.php
├── check_real_database.php
├── check_real_projects_data.php
├── check_receipt_path.php
├── check_room_improvement_database.php
├── check_room_improvement_images.php
├── check_schema.php
├── check_shijin_construction_project.php
├── check_shijin_mysql_project.php
├── check_shijin_project_issue.php
├── check_stage_payment_columns.php
├── check_stage_payment_structure.php
├── check_stage_payment_tables.php
├── check_system_status.bat
├── check_table_collations.php
├── check_table_structure.php
├── check_tables.php
├── check_tables_38.php
├── check_tables_exist.php
├── check_technical_details_site_data.php
├── check_timeline_data.php
├── check_used_dates.php
├── check_user_32_projects.php
├── check_users_table.php
├── check_weekly_monthly_structure.php
├── check_weekly_tables.php
├── clean_architect_selection_form.html
├── clean_sample_payment_requests.php
├── clear_audit_entries.php
├── compare_weekly_tables.php
├── complete_brickwork_and_final_stages.php
├── complete_project_37_all_stages.php
├── complete_project_37_construction.php
├── complete_project_37_corrected.php
├── complete_project_37_final.php
├── complete_project_37_final_fix.php
├── complete_project_37_working.php
├── complete_project_38_construction.php
├── complete_project_38_sqlite.php
├── complete_project_3_construction.php
├── complete_project_3_fresh.php
├── composer.json
├── contractor_documents_ui_changes.md
├── contractor_summary_demo.html
├── cookie.txt
├── create_additional_test_data.php
├── create_ai_predictions_table.php
├── create_concept_previews_sqlite.php
├── create_contractor_document_audit_table.php
├── create_contractor_stage_documents_table.php
├── create_custom_payment_table.php
├── create_estimate_and_project_for_user_32.php
├── create_final_inspection_for_user_32.php
├── create_minimal_inspection_for_user_32.php
├── create_ml_analytics_tables.php
├── create_more_test_concepts.php
├── create_payment_id_13.php
├── create_project_and_inspection_for_user_32.php
├── create_project_for_user_32_fixed.php
├── create_sample_inspection_reports.php
├── create_simple_project_for_user_32.php
├── create_stage_document_requirements_table.php
├── create_test_contractor_stage_documents.php
├── create_test_custom_payment.php
├── create_test_daily_progress.php
├── create_test_data_simple.php
├── create_test_image.php
├── create_test_layout_send_with_site_details.php
├── create_test_payment_for_homeowner_32.php
├── create_test_progress_for_contractor_32.php
├── create_test_receipt_for_homeowner_32.php
├── create_test_room_image.php
├── create_valid_project.php
├── debug_api.php
├── debug_api_response.php
├── debug_browser_session.php
├── debug_conceptual_data.php
├── debug_conceptual_image_visibility.html
├── debug_contractor_bank_transfer_receipt.php
├── debug_contractor_receipt_upload.php
├── debug_contractor_receipt_verification.php
├── debug_current_validation_issue.php
├── debug_custom_payment_access.php
├── debug_estimation_form_data.html
├── debug_homeowner_dashboard.html
├── debug_homeowner_reports.php
├── debug_inspection_reports_visibility.php
├── debug_inspector_api.php
├── debug_inspector_projects.php
├── debug_payment_flow.html
├── debug_payment_project_mapping.php
├── debug_payment_receipt_issue.php
├── debug_progress_api_direct.php
├── debug_progress_value_processing.php
├── debug_project_37_progress.php
├── debug_project_access.php
├── debug_project_data.php
├── debug_receipt_upload_issue.php
├── debug_receipt_upload_real_time.php
├── debug_room_improvement.html
├── debug_stage_validation_issue.php
├── debug_table_structure.php
├── debug_unified_api_params.php
├── debug_update_counts.php
├── debug_update_history_counts.php
├── demo_blockchain_audit_trail.html
├── demo_immutable_audit_system.html
├── demonstrate_blockchain_audit_system.php
├── establish_contractor_session.php
├── establish_homeowner_session.php
├── execute_payment_cleanup.php
├── final_project_name_fix.php
├── finalize_project_37_completion.php
├── find_project_38.php
├── find_project_tables.php
├── find_real_projects_with_data.php
├── fix_all_contractor_payment_mismatches.php
├── fix_contractor_assignment_payment_16.php
├── fix_corrupted_receipt.php
├── fix_duplicate_project_names.php
├── fix_homeowner_authentication.php
├── fix_homeowner_images.js
├── fix_missing_components.php
├── fix_mysql_concepts.php
├── fix_payment_15_ownership.php
├── fix_payment_history_complete.php
├── fix_payment_receipt_upload_issue.php
├── fix_progress_percentage_precision.php
├── fix_progress_project_ids.php
├── fix_progress_report_contractor.php
├── fix_project_4_completion.php
├── fix_receipt_upload_complete.php
├── fix_room_improvement_image_links.php
├── fix_room_improvement_images.php
├── fix_session_auth.php
├── fix_stuck_concepts.php
├── fix_weekly_table_references.php
├── generate_real_ml_predictions.php
├── get_correct_daily_progress.php
├── get_financial_summary.php
├── get_full_inspection_details.php
├── get_key_financial_data.php
├── hashing_visual_guide.html
├── homeowner_dashboard_enhanced.html
├── improve_project_names.php
├── install_ai_self_evaluation.bat
├── install_schedule_tracking.bat
├── list_all_projects.php
├── login_homeowner.html
├── login_homeowner_properly.php
├── login_result.html
├── ml_analytics_dashboard_demo.html
├── package-lock.json
├── package.json
├── payment_verification_hashing_example.php
├── playwright.config.js
├── populate_ai_evaluation_with_synthetic_data.php
├── populate_existing_payments_audit.bat
├── quick-test.bat
├── quick_ai_schema_setup.sql
├── quick_login_homeowner.php
├── restart_ai_service_with_real_images.bat
├── restore_original_inspection_report.php
├── rfg.txt
├── room_improvement_direct.html
├── run-architect-design-editor-test.bat
├── run-architect-design-editor-tests.bat
├── run-construction-progress-tests.bat
├── run-progress-tests.bat
├── run_ml_analytics_setup.php
├── run_overrun_test.php
├── schedule_tracking_visual_guide.html
├── server.js
├── setup_collaborative_ai.py
├── setup_complete_project_37.php
├── setup_immutable_audit_system.php
├── setup_jwt_auth.bat
├── setup_minimal_database.php
├── setup_ml_analytics.html
├── setup_project_37_complete.php
├── setup_simple_receipt_system.php
├── setup_site_inspector_dashboard.php
├── show_project_3_current_status.html
├── show_receipt_storage_info.php
├── simple_ai_test.php
├── simulate_completed_projects_for_ai_testing.php
├── start-server-and-test.bat
├── start_ai_service.bat
├── start_collaborative_ai.bat
├── start_risk_service.bat
├── start_server.bat
├── tatus
├── test-overrun-system.bat
├── test_admin_inspection_reports_integration.html
├── test_ai_self_evaluation.html
├── test_ai_self_evaluation.php
├── test_ai_self_evaluation_system.html
├── test_all_real_projects_api.php
├── test_api_direct.php
├── test_api_endpoints_direct.php
├── test_api_http_access.php
├── test_api_response.php
├── test_api_with_curl.php
├── test_bills_receipts_api.html
├── test_bills_receipts_integration.html
├── test_blockchain_functionality.php
├── test_blockchain_usefulness_today.php
├── test_building_size_input_fix.html
├── test_clean_inspection_api.php
├── test_complete_homeowner_flow.html
├── test_complete_progress_fix.html
├── test_complete_progress_flow.php
├── test_complete_real_projects_system.html
├── test_complete_receipt_upload.html
├── test_completed_project_overruns.html
├── test_completed_projects_section.html
├── test_contractor_api_fixed.php
├── test_contractor_api_project_3.php
├── test_contractor_daily_reports_fix.html
├── test_contractor_dashboard_view.php
├── test_contractor_documents_simplified.html
├── test_contractor_payment_history_api.php
├── test_contractor_project_details.php
├── test_contractor_projects_api.php
├── test_contractor_receipt_upload_fix.php
├── test_contractor_upload_complete_fix.php
├── test_contractor_verification_api.php
├── test_corrected_daily_progress_system.html
├── test_cost_time_overrun_system.html
├── test_cost_time_overrun_system.php
├── test_design_deletion.html
├── test_document_management_system.html
├── test_document_preview.html
├── test_enhanced_inspection_system.html
├── test_enhanced_project_details.php
├── test_existing_payment_audit_trails.php
├── test_final_contractor_dashboard.php
├── test_final_contractor_upload_system.php
├── test_fixed_api.php
├── test_frontend_api_integration.html
├── test_frontend_data_processing.html
├── test_frontend_integration.html
├── test_frontend_progress_fix.html
├── test_homeowner_32_payments.php
├── test_homeowner_contractor_documents_display.php
├── test_homeowner_contractor_documents_fix.html
├── test_homeowner_contractor_documents_frontend.html
├── test_homeowner_contractor_documents_integration.html
├── test_homeowner_dashboard_api.php
├── test_homeowner_dashboard_complete.html
├── test_homeowner_dashboard_image_fix.html
├── test_homeowner_dashboard_integration.html
├── test_homeowner_document_viewer_no_project_selection.html
├── test_homeowner_documents_no_project_selection.html
├── test_homeowner_payment_filtering.html
├── test_homeowner_payment_requests_api.php
├── test_homeowner_receipt_display_fix.html
├── test_homeowner_received_designs.html
├── test_homeowner_session.php
├── test_house_plan_layout_submission.html
├── test_house_plan_save_persistence.html
├── test_image_fixes.html
├── test_immutable_audit_system.php
├── test_input_behavior.html
├── test_inspection_api_direct.php
├── test_inspection_reports_frontend.html
├── test_inspection_reports_http.html
├── test_inspection_reports_visibility.php
├── test_inspector_api.php
├── test_inspector_api_direct.php
├── test_inspector_apis.php
├── test_inspector_frontend.html
├── test_inspector_real_progress.php
├── test_integrated_document_management.html
├── test_json_sort_keys_comprehensive_fix.php
├── test_json_sort_keys_fix.php
├── test_jwt_implementation.php
├── test_jwt_simple.php
├── test_layout_submission_direct.html
├── test_ml_analytics_api.html
├── test_ml_api.php
├── test_overrun_api.php
├── test_overrun_project_3.php
├── test_overrun_system_simple.php
├── test_overrun_with_existing_project.php
├── test_payment_api_response.php
├── test_payment_filtering_logic.php
├── test_payment_flow_fix.html
├── test_payment_receipt_upload_fix.html
├── test_payment_requests_api.php
├── test_payment_verification_fix.php
├── test_progress_data_fix.php
├── test_progress_documents_integration.html
├── test_progress_fix.html
├── test_progress_fix_simple.html
├── test_progress_input_fix.html
├── test_progress_input_issue.html
├── test_progress_percentage_fix.html
├── test_project_37_complete.html
├── test_project_details_api.php
├── test_project_details_http.php
├── test_project_dropdown.php
├── test_project_dropdown_display.html
├── test_project_inspection_reports.html
├── test_project_overview_fix.html
├── test_project_selection_fix.html
├── test_real_progress_calculation.php
├── test_real_progress_dashboard.html
├── test_real_project_analytics.php
├── test_receipt_access.php
├── test_receipt_upload_api.php
├── test_receipt_upload_direct.html
├── test_receipt_upload_fix.html
├── test_receipt_upload_fix.php
├── test_receipt_upload_payment_16.html
├── test_receipt_upload_scrolling.html
├── test_receipt_upload_scrolling_fix.html
├── test_receipt_verification_blockchain_integration.php
├── test_receipt_viewing_fix.html
├── test_retraining_api.bat
├── test_retraining_api.sh
├── test_risk_assessment_frontend.html
├── test_risk_blocking_validation.html
├── test_risk_service_complete.html
├── test_schedule_tracking.html
├── test_schedule_tracking_system.html
├── test_simple_inspector_progress.php
├── test_simple_progress_input.html
├── test_simple_query.php
├── test_simple_receipt_blockchain_hashing.php
├── test_simple_receipt_system.html
├── test_simplified_bills_receipts.html
├── test_simplified_contractor_documents.html
├── test_site_inspection_budget_fix.html
├── test_site_inspection_dashboard.html
├── test_site_inspection_fixes.html
├── test_site_inspection_progress.php
├── test_site_inspection_tabs_integration.html
├── test_sql_fix_direct.php
├── test_stage_completion_flow.html
├── test_stage_documents_api.php
├── test_stage_documents_fix.php
├── test_stage_validation_fix.html
├── test_universal_receipt_upload.php
├── test_universal_receipt_upload_frontend.html
├── test_updated_dashboard_integration.php
├── test_upload_after_login.php
├── test_verification_removal_complete.html
├── test_weekly_count.php
├── update_payment_15_status.php
├── update_project_status.php
├── verify_ai_setup.py
├── verify_and_finalize_project_37.php
├── verify_contractor_dashboard_changes.html
├── verify_contractor_receipt_upload_fix.php
├── verify_overrun_apis.php
├── verify_project_3_completion.php
├── verify_project_3_data.php
├── verify_real_progress_implementation.php
├── verify_room_improvement_separation.html
├── verify_schedule_tracking_migration.php
├── verify_system_database.php
├── view_uploaded_receipts.html
└── visual_blockchain_demo.html
```

---
*Generated by FileTree Pro Extension*