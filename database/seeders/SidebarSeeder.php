<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sidebar;

class SidebarSeeder extends Seeder
{
    public function run(): void
    {
        Sidebar::truncate();

        /* ────────────── HR & SUPERVISOR ────────────── */

        Sidebar::create([
            'title'     => 'Dashboard',
            'route'     => 'dashboard',
            'icon'      => 'speedometer2',
            'parent_id' => null,
            'order'     => 1,
            'roles'     => ['hr','supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Announcements',
            'route'     => 'announcements.index',
            'icon'      => 'megaphone',
            'parent_id' => null,
            'order'     => 2,
            'roles'     => ['hr','supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'User List',
            'route'     => 'users.index',
            'icon'      => 'people',
            'parent_id' => null,
            'order'     => 3,
            'roles'     => ['hr'],
        ]);

        Sidebar::create([
            'title'     => 'Approvals',
            'route'     => 'approvals.index',
            'icon'      => 'check-circle',
            'parent_id' => null,
            'order'     => 4,
            'roles'     => ['hr','supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Performance Evaluation',
            'route'     => 'evaluations.index',
            'icon'      => 'clipboard2-check',
            'parent_id' => null,
            'order'     => 5,
            'roles'     => ['supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Employee List',
            'route'     => 'employees.index',
            'icon'      => 'person-lines-fill',
            'parent_id' => null,
            'order'     => 6,
            'roles'     => ['hr','supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Attendance List',
            'route'     => 'attendance.index',
            'icon'      => 'clipboard-data',
            'parent_id' => null,
            'order'     => 7,
            'roles'     => ['hr','supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Schedule Management',
            'route'     => 'schedule.index',
            'icon'      => 'calendar-check',
            'parent_id' => null,
            'order'     => 8,
            'roles'     => ['supervisor'],
        ]);

        Sidebar::create([
            'title'     => 'Payroll List',
            'route'     => 'payroll.index',
            'icon'      => 'file-earmark-text',
            'parent_id' => null,
            'order'     => 9,
            'roles'     => ['hr'],
        ]);

        Sidebar::create([
            'title'     => 'Offboarding',
            'route'     => 'offboarding.index',
            'icon'      => 'box-arrow-right',
            'parent_id' => null,
            'order'     => 10,
            'roles'     => ['hr'],
        ]);

        Sidebar::create([
            'title'     => 'Reports',
            'route'     => 'reports.index',
            'icon'      => 'file-earmark-bar-graph',
            'parent_id' => null,
            'order'     => 11,
            'roles'     => ['hr'],
        ]);

        Sidebar::create([
            'title'     => 'Employee Documents',
            'route'     => 'documents.index',
            'icon'      => 'folder2-open',
            'parent_id' => null,
            'order'     => 12,
            'roles'     => ['hr'],
        ]);

        Sidebar::create([
            'title'     => 'Face Recognition',
            'route'     => 'face.index',
            'icon'      => 'camera-video',
            'parent_id' => null,
            'order'     => 13,
            'roles'     => ['hr'],
        ]);

  


        /* ────────────── EMPLOYEE SELF-SERVICE ────────────── */

        Sidebar::create([
            'title'     => 'My Dashboard',
            'route'     => 'dashboard.employee',
            'icon'      => 'house',
            'parent_id' => null,
            'order'     => 20,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Time Card',
            'route'     => 'employee.timecard.index',
            'icon'      => 'journal-check',
            'parent_id' => null,
            'order'     => 21,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Schedule',
            'route'     => 'employees.schedule',
            'icon'      => 'calendar-week',
            'parent_id' => null,
            'order'     => 22,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Leave Requests',
            'route'     => 'leaves.index',
            'icon'      => 'calendar',
            'parent_id' => null,
            'order'     => 23,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Evaluations',
            'route'     => 'my.evaluations.index',
            'icon'      => 'clipboard2-check',
            'parent_id' => null,
            'order'     => 24,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Loans',
            // ✅ correct route name for employee loans
            'route'     => 'employee.loans.index',
            'icon'      => 'piggy-bank',
            'parent_id' => null,
            'order'     => 25,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Payslips',
            'route'     => 'payslips.index',
            'icon'      => 'file-earmark-text',
            'parent_id' => null,
            'order'     => 26,
            'roles'     => ['employee'],
        ]);

        Sidebar::create([
            'title'     => 'My Documents',
            'route'     => 'mydocs.index',
            'icon'      => 'files',
            'parent_id' => null,
            'order'     => 27,
            'roles'     => ['employee'],
        ]);
    

          Sidebar::create([
            'title'     => 'Formal Complaints',
            'route'     => 'concerns.index',
            'icon'      => 'exclamation-triangle',
            'parent_id' => null,
            'order'     => 14,
            'roles'     => ['hr','supervisor','employee'],
        ]);
}
}