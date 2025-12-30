# Requirements Document

## Introduction

The BuildHub platform follows a structured construction workflow: homeowner requirement submission → architect selection → layout preparation → contractor estimation → estimate approval → construction. Currently, there is a gap between estimate approval and construction progress tracking, where projects that have reached the construction-ready state are not appearing in the contractor's progress update section. This feature extends the existing workflow by introducing proper state management for the construction phase.

## Glossary

- **Project**: A construction project initiated by a homeowner with associated estimates and layouts
- **Estimate**: A cost and timeline proposal submitted by a contractor for a specific project
- **Construction_State**: The current phase of a project in the construction workflow
- **Progress_Update_System**: The contractor interface for submitting construction progress reports
- **State_Transition**: The process of moving a project from one phase to another in the workflow

## Requirements

### Requirement 1: Construction State Management

**User Story:** As a system administrator, I want projects to have clear construction states, so that the workflow progression is properly tracked and managed.

#### Acceptance Criteria

1. THE System SHALL maintain a construction state for each project with values: pending, estimated, approved, construction_started, in_progress, completed, cancelled
2. WHEN an estimate is approved by a homeowner, THE System SHALL transition the project state from 'approved' to 'construction_started'
3. WHEN the first progress update is submitted, THE System SHALL transition the project state from 'construction_started' to 'in_progress'
4. THE System SHALL prevent state transitions that violate the workflow sequence
5. THE System SHALL log all state transitions with timestamps and user information

### Requirement 2: Estimate Approval to Construction Transition

**User Story:** As a homeowner, I want to formally start construction after approving an estimate, so that the contractor can begin progress tracking.

#### Acceptance Criteria

1. WHEN a homeowner approves an estimate, THE System SHALL provide an option to "Start Construction"
2. WHEN "Start Construction" is selected, THE System SHALL transition the project to 'construction_started' state
3. THE System SHALL send a notification to the assigned contractor about construction start
4. THE System SHALL record the construction start date and time
5. THE System SHALL prevent multiple construction starts for the same project

### Requirement 3: Construction-Ready Project Filtering

**User Story:** As a contractor, I want to see only construction-ready projects in my progress update section, so that I can submit updates for active construction projects.

#### Acceptance Criteria

1. THE Progress_Update_System SHALL display only projects with state 'construction_started' or 'in_progress'
2. WHEN a contractor accesses the progress update interface, THE System SHALL filter projects by contractor assignment and construction state
3. THE System SHALL display project details including homeowner name, location, timeline, and current state
4. THE System SHALL prevent progress updates for projects not in construction phase
5. THE System SHALL show clear indicators for project state in the contractor interface

### Requirement 4: State Transition API

**User Story:** As a developer, I want consistent API endpoints for state transitions, so that the frontend can properly manage project states.

#### Acceptance Criteria

1. THE System SHALL provide an API endpoint to transition project state from 'approved' to 'construction_started'
2. THE System SHALL validate state transitions and return appropriate error messages for invalid transitions
3. THE System SHALL update related tables (contractor_send_estimates, project status) when state changes
4. THE System SHALL maintain referential integrity during state transitions
5. THE System SHALL return updated project information after successful state transition

### Requirement 5: Backward Compatibility

**User Story:** As a system maintainer, I want existing projects to work with the new state system, so that current users are not disrupted.

#### Acceptance Criteria

1. THE System SHALL automatically assign appropriate states to existing projects based on their current status
2. WHEN migrating existing data, THE System SHALL set projects with approved estimates to 'approved' state
3. THE System SHALL set projects with existing progress updates to 'in_progress' state
4. THE System SHALL preserve all existing project data during state migration
5. THE System SHALL provide a rollback mechanism for the state migration

### Requirement 6: State Visibility and Tracking

**User Story:** As a homeowner, I want to see the current state of my project, so that I understand where it is in the construction workflow.

#### Acceptance Criteria

1. THE System SHALL display the current project state in the homeowner dashboard
2. WHEN viewing project details, THE System SHALL show a visual workflow indicator with current state highlighted
3. THE System SHALL display state transition history with dates and responsible users
4. THE System SHALL provide estimated timelines for upcoming state transitions
5. THE System SHALL send notifications to homeowners when project state changes

### Requirement 7: Contractor Assignment Validation

**User Story:** As a contractor, I want to ensure I can only update progress for projects I am assigned to, so that project security is maintained.

#### Acceptance Criteria

1. THE System SHALL validate contractor assignment before allowing progress updates
2. WHEN a contractor attempts to access a project, THE System SHALL verify the contractor is assigned to that specific project
3. THE System SHALL return appropriate error messages for unauthorized access attempts
4. THE System SHALL log all access attempts for security auditing
5. THE System SHALL prevent contractors from viewing projects assigned to other contractors

### Requirement 8: State Transition Notifications

**User Story:** As a project stakeholder, I want to receive notifications when project states change, so that I stay informed about project progress.

#### Acceptance Criteria

1. WHEN a project state transitions to 'construction_started', THE System SHALL notify the assigned contractor
2. WHEN a project state transitions to 'in_progress', THE System SHALL notify the homeowner
3. THE System SHALL send email notifications for major state transitions
4. THE System SHALL create in-app notifications for all state changes
5. THE System SHALL allow users to configure notification preferences for state transitions