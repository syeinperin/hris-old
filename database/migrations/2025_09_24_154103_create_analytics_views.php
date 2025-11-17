<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        /* ===================== HR KPIs ===================== */

        // 1) Headcount by month & department
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_headcount AS
        SELECT
            DATE_FORMAT(e.employment_start_date, '%Y-%m-01') AS month,
            d.name AS department,
            COUNT(*) AS headcount
        FROM employees e
        LEFT JOIN departments d ON d.id = e.department_id
        WHERE e.deleted_at IS NULL
          AND (e.employment_end_date IS NULL OR e.employment_end_date >= NOW())
        GROUP BY DATE_FORMAT(e.employment_start_date, '%Y-%m-01'), d.name
        ORDER BY month, department;
        SQL);

        // 2) Turnover (separations per month)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_turnover AS
        SELECT
            DATE_FORMAT(o.effective_date, '%Y-%m-01') AS month,
            COUNT(*) AS separations
        FROM offboardings o
        WHERE o.deleted_at IS NULL
          AND o.status = 'completed'
        GROUP BY DATE_FORMAT(o.effective_date, '%Y-%m-01')
        ORDER BY month;
        SQL);

        // 3) Time-to-hire (avg days)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_time_to_hire AS
        SELECT
            DATE_FORMAT(e.employment_start_date, '%Y-%m-01') AS month,
            AVG(DATEDIFF(e.employment_start_date, e.application_date)) AS avg_days
        FROM employees e
        WHERE e.deleted_at IS NULL
          AND e.application_date IS NOT NULL
          AND e.employment_start_date IS NOT NULL
        GROUP BY DATE_FORMAT(e.employment_start_date, '%Y-%m-01')
        ORDER BY month;
        SQL);

        // 4) Absenteeism (% of days with no time_in)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_absenteeism AS
        SELECT
            DATE_FORMAT(a.date, '%Y-%m-01') AS month,
            (SUM(CASE WHEN a.time_in IS NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 AS rate_pct
        FROM (
            SELECT DATE(a.time_in) AS date, a.employee_id, a.time_in
            FROM attendances a
        ) a
        GROUP BY DATE_FORMAT(a.date, '%Y-%m-01')
        ORDER BY month;
        SQL);

        // 5) OT cost (dummy 100/hr beyond 8)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_ot_cost AS
        SELECT
            DATE_FORMAT(a.time_in, '%Y-%m-01') AS month,
            SUM(GREATEST(TIMESTAMPDIFF(HOUR, a.time_in, a.time_out) - 8, 0) * 100) AS total_cost
        FROM attendances a
        WHERE a.time_in IS NOT NULL AND a.time_out IS NOT NULL
        GROUP BY DATE_FORMAT(a.time_in, '%Y-%m-01')
        ORDER BY month;
        SQL);

        // 6) Gender mix
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_kpi_gender_mix AS
        SELECT
            DATE_FORMAT(e.employment_start_date, '%Y-%m-01') AS month,
            e.gender,
            COUNT(*) AS count_gender
        FROM employees e
        WHERE e.deleted_at IS NULL
        GROUP BY DATE_FORMAT(e.employment_start_date, '%Y-%m-01'), e.gender
        ORDER BY month, e.gender;
        SQL);

        /* ============== Supervisor Team Attendance ============== */

        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW v_sc_team_attendance AS
        SELECT
            e.supervisor_id,
            DATE(a.time_in) AS day,
            ROUND(
                (SUM(CASE WHEN a.time_in IS NOT NULL THEN 1 ELSE 0 END) / COUNT(a.id)) * 100,
                2
            ) AS present_pct
        FROM employees e
        JOIN attendances a ON e.id = a.employee_id
        WHERE e.deleted_at IS NULL
        GROUP BY e.supervisor_id, DATE(a.time_in)
        ORDER BY day;
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_kpi_headcount');
        DB::statement('DROP VIEW IF EXISTS v_kpi_turnover');
        DB::statement('DROP VIEW IF EXISTS v_kpi_time_to_hire');
        DB::statement('DROP VIEW IF EXISTS v_kpi_absenteeism');
        DB::statement('DROP VIEW IF EXISTS v_kpi_ot_cost');
        DB::statement('DROP VIEW IF EXISTS v_kpi_gender_mix');
        DB::statement('DROP VIEW IF EXISTS v_sc_team_attendance');
    }
};
