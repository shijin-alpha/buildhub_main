# Site Inspector Dashboard

A dedicated, task-oriented interface designed specifically for administrative assistants performing site inspection activities within the BuildHub construction management system.

## Overview

The Site Inspector Dashboard is visually and functionally distinct from the main Admin dashboard to clearly reflect the limited and specialized responsibilities of the Site Inspector. It provides a comprehensive solution for construction site monitoring, inspection reporting, and quality assurance.

## Key Features

### 🏗️ **Project Overview**
- **At-a-glance site overview**: View all assigned construction sites with project name, location, current stage, and inspection status
- **Summary metrics**: Total assigned sites, pending inspections, completed inspections, and recent updates
- **Project filtering**: Easy navigation through assigned projects with status-based filtering

### 📋 **Inspection Management**
- **Structured inspection reports**: Create detailed reports with standardized checklists
- **Multiple inspection types**: Routine, milestone, quality, safety, and final inspections
- **Quality scoring**: Rate construction quality on a 1-10 scale
- **Safety compliance tracking**: Monitor and report safety protocol adherence

### 📸 **Photo Documentation**
- **Geo-tagged photo uploads**: Automatic GPS coordinate capture for location verification
- **Categorized photos**: Progress, issue, quality, safety, and completion photo types
- **Document support**: Upload PDF reports and documentation alongside photos
- **Caption and metadata**: Add descriptions and context to uploaded media

### 📊 **Progress Tracking**
- **Real-time updates**: View latest construction progress from contractors
- **Stage-based monitoring**: Track progress through construction phases
- **Historical data**: Access complete inspection and progress history
- **Timeline visualization**: See project progression over time

### 🔔 **Notification System**
- **Automatic alerts**: Notify homeowners and contractors of inspection results
- **Status-based notifications**: Different alerts for approved, rejected, or attention-required inspections
- **Admin escalation**: Automatic admin notification for critical issues

### 🔒 **Security & Access Control**
- **Role-based access**: Strict limitations to assigned projects only
- **Read-only project data**: Cannot modify core project information
- **Secure authentication**: Session-based login with role verification
- **Audit trail**: Complete logging of all inspection activities

## Technical Architecture

### Frontend Components
- **SiteInspectorDashboard.jsx**: Main dashboard interface
- **SiteInspectorLogin.jsx**: Dedicated login portal
- **InspectionReportForm.jsx**: Comprehensive inspection form
- **InspectionHistory.jsx**: Historical data viewer
- **ProjectDetailsModal.jsx**: Read-only project information

### Backend API Endpoints
- **`/api/inspector/get_assigned_projects.php`**: Fetch assigned projects
- **`/api/inspector/get_project_details.php`**: Detailed project information
- **`/api/inspector/create_inspection_report.php`**: Submit inspection reports
- **`/api/inspector/upload_inspection_photos.php`**: Handle file uploads
- **`/api/inspector/get_inspection_history.php`**: Retrieve inspection history

### Database Schema
- **`site_inspector_assignments`**: Project-inspector mappings
- **`inspection_reports`**: Core inspection data
- **`inspection_photos`**: Photo and document storage
- **`inspection_checklist_items`**: Structured inspection items
- **`inspection_notifications`**: Stakeholder alerts

## Installation & Setup

### 1. Run Setup Script
```bash
# Navigate to your BuildHub root directory
cd /path/to/buildhub

# Run the setup script via web browser
http://localhost/buildhub/setup_site_inspector_dashboard.php
```

### 2. Database Configuration
The setup script will automatically:
- Update the `users` table to support `site_inspector` role
- Create all necessary inspection-related tables
- Set up proper foreign key relationships
- Create sample data for testing

### 3. Default Credentials
```
Email: inspector@buildhub.com
Password: inspector123
```

### 4. Directory Structure
```
buildhub/
├── backend/
│   ├── api/inspector/          # Inspector-specific API endpoints
│   ├── uploads/inspection_photos/  # Photo storage directory
│   └── database/site_inspector_schema.sql
├── frontend/src/
│   ├── components/
│   │   ├── SiteInspectorDashboard.jsx
│   │   ├── SiteInspectorLogin.jsx
│   │   ├── InspectionReportForm.jsx
│   │   ├── InspectionHistory.jsx
│   │   └── ProjectDetailsModal.jsx
│   └── styles/
│       ├── SiteInspectorDashboard.css
│       ├── SiteInspectorLogin.css
│       ├── InspectionReportForm.css
│       ├── InspectionHistory.css
│       └── ProjectDetailsModal.css
```

## User Workflow

### 1. **Login Process**
- Access dedicated Site Inspector login portal
- Secure authentication with role verification
- Automatic redirect to inspector dashboard

### 2. **Daily Operations**
1. **Review Assigned Projects**: Check dashboard for project updates and pending inspections
2. **Site Inspection**: Visit construction sites and perform inspections
3. **Create Reports**: Use structured forms to document findings
4. **Upload Evidence**: Add geo-tagged photos and supporting documents
5. **Submit Reports**: Complete and submit inspection reports
6. **Track History**: Review past inspections and follow up on issues

### 3. **Inspection Report Creation**
1. Select project from assigned list
2. Choose inspection type and stage
3. Complete structured checklist items
4. Add detailed notes and recommendations
5. Upload photos with GPS coordinates
6. Set overall status and quality score
7. Submit report (automatically notifies stakeholders)

## Integration with BuildHub

### Authentication System
- Extends existing BuildHub authentication
- Supports site_inspector role alongside homeowner, contractor, architect
- Session-based security with proper CORS handling

### Project Management
- Integrates with existing `construction_projects` table
- Maintains read-only access to project data
- Links with contractor progress updates and homeowner communications

### File Management
- Uses existing upload infrastructure
- Supports geo-tagged photos with GPS metadata
- Automatic file validation and security checks

### Notification System
- Extends existing notification framework
- Automatic alerts to project stakeholders
- Admin escalation for critical issues

## API Documentation

### Get Assigned Projects
```http
GET /api/inspector/get_assigned_projects.php
```
Returns list of projects assigned to the authenticated inspector with summary statistics.

### Create Inspection Report
```http
POST /api/inspector/create_inspection_report.php
Content-Type: application/json

{
  "project_id": 123,
  "inspection_date": "2024-01-15",
  "inspection_stage": "Foundation",
  "inspection_type": "quality",
  "overall_status": "approved",
  "quality_score": 8.5,
  "safety_compliance": "compliant",
  "notes": "Foundation work completed to specifications",
  "recommendations": "Proceed to next stage",
  "checklist_items": [...]
}
```

### Upload Inspection Photos
```http
POST /api/inspector/upload_inspection_photos.php
Content-Type: multipart/form-data

inspection_report_id: 456
photos[]: [file1, file2, ...]
captions[]: ["Foundation pour", "Rebar placement"]
photo_types[]: ["progress", "quality"]
latitude[]: [12.345678, 12.345679]
longitude[]: [77.123456, 77.123457]
```

## Security Considerations

### Access Control
- **Project Assignment Verification**: All API endpoints verify inspector has access to requested project
- **Role-Based Permissions**: Strict enforcement of site_inspector role requirements
- **Read-Only Project Data**: Inspectors cannot modify core project information

### Data Protection
- **Session Security**: Secure session management with proper cookie settings
- **File Upload Validation**: Strict file type and size validation
- **SQL Injection Prevention**: Prepared statements for all database queries

### Audit Trail
- **Complete Logging**: All inspection activities are logged with timestamps
- **User Attribution**: All actions tied to specific inspector accounts
- **Change Tracking**: Full history of inspection reports and modifications

## Customization Options

### Inspection Checklists
- **Configurable Categories**: Foundation, Structure, Electrical, Plumbing, Safety, Quality
- **Custom Items**: Add project-specific inspection criteria
- **Priority Levels**: Low, Medium, High, Critical priority assignments

### Photo Categories
- **Progress Photos**: Document construction advancement
- **Issue Photos**: Capture problems or defects
- **Quality Photos**: Document workmanship standards
- **Safety Photos**: Record safety compliance
- **Completion Photos**: Final stage documentation

### Notification Templates
- **Customizable Messages**: Modify notification content for different scenarios
- **Recipient Rules**: Configure who receives notifications based on inspection status
- **Escalation Paths**: Define admin notification triggers

## Troubleshooting

### Common Issues

1. **Login Problems**
   - Verify site_inspector role is properly set in database
   - Check user status is 'approved' and is_verified is 1
   - Ensure session cookies are properly configured

2. **File Upload Failures**
   - Check upload directory permissions (755)
   - Verify file size limits (10MB default)
   - Ensure supported file types (JPG, PNG, PDF)

3. **Project Access Denied**
   - Verify inspector is assigned to the project in site_inspector_assignments table
   - Check assignment status is 'active'
   - Ensure project exists and is not deleted

### Database Maintenance

```sql
-- Check inspector assignments
SELECT sia.*, cp.project_name, u.first_name, u.last_name 
FROM site_inspector_assignments sia
JOIN construction_projects cp ON sia.project_id = cp.id
JOIN users u ON sia.inspector_id = u.id
WHERE sia.status = 'active';

-- View recent inspection reports
SELECT ir.*, cp.project_name, u.first_name, u.last_name
FROM inspection_reports ir
JOIN construction_projects cp ON ir.project_id = cp.id
JOIN users u ON ir.inspector_id = u.id
ORDER BY ir.created_at DESC
LIMIT 10;

-- Check upload directory usage
SELECT 
  COUNT(*) as total_photos,
  SUM(file_size) as total_size_bytes,
  AVG(file_size) as avg_size_bytes
FROM inspection_photos;
```

## Future Enhancements

### Planned Features
- **Mobile App**: Native mobile application for field inspections
- **Offline Mode**: Work without internet connection, sync when available
- **Advanced Analytics**: Inspection trends and quality metrics
- **Integration APIs**: Connect with external inspection tools
- **Automated Reporting**: Generate PDF reports automatically
- **QR Code Integration**: Quick project access via QR codes

### Performance Optimizations
- **Image Compression**: Automatic photo optimization for storage
- **Caching Layer**: Redis caching for frequently accessed data
- **Database Indexing**: Optimize queries for large datasets
- **CDN Integration**: Fast photo delivery via content delivery network

## Support & Maintenance

### Regular Maintenance Tasks
1. **Database Cleanup**: Archive old inspection reports periodically
2. **File Management**: Clean up orphaned photo files
3. **Performance Monitoring**: Track API response times and database performance
4. **Security Updates**: Keep authentication and file upload security current

### Monitoring & Logging
- **Error Logging**: All errors logged to `/backend/error.log`
- **Access Logging**: Track inspector login and activity patterns
- **Performance Metrics**: Monitor inspection report creation times
- **Storage Usage**: Track photo upload storage consumption

---

## Contact & Support

For technical support or feature requests related to the Site Inspector Dashboard, please contact the BuildHub development team or create an issue in the project repository.

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Compatibility**: BuildHub v2.0+