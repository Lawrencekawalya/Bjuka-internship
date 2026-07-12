import type { User } from './auth';

export interface ApprovedNetwork {
    id: number;
    name: string;
    ssid: string;
    bssid: string;
}

export interface InternshipBatch {
    id: string;
    batch_code: string;
    name: string;
    description: string | null;
    report_format_text?: string | null;
    report_format_path?: string | null;
    report_format_original_name?: string | null;
    start_date: string;
    end_date: string;
    capacity: number;
    expected_working_days: number;
    status: string;
    virtual_status: string;
    progress_percentage: number;
    coordinator_id: number | null;
    coordinator?: User;
    interns?: Intern[];
    interns_count?: number;
    approved_networks?: ApprovedNetwork[];
    created_at: string;
    updated_at: string;
}

export interface Intern {
    id: string;
    phone: string | null;
    institution: string | null;
    course: string | null;
    registration_number: string | null;
    status: string;
    certificate_path?: string | null;
    certificate_url?: string | null;
    report_generation_quota?: {
        generation_count: number;
        generation_limit: number;
        reset_requested_at: string | null;
        reset_approved_at: string | null;
        reset_used: boolean;
        permanently_locked_at: string | null;
    } | null;
    user?: User;
}

export interface BatchStats {
    total_interns: number;
    present_today: number;
    attendance_rate: number;
    total_supervisors: number;
    progress: number;
}

export interface BatchPerformanceAnalytics {
    overview: {
        elapsed_working_days: number;
        expected_records: number;
        actual_records: number;
        missing_records: number;
        average_hours_per_attendance: number;
        at_risk_interns: number;
    };
    daily_attendance: {
        date: string;
        label: string;
        present: number;
        late: number;
        partial: number;
        absent: number;
        attendance_rate: number;
        interns: {
            present: string[];
            late: string[];
            partial: string[];
            absent: string[];
        };
    }[];
    status_distribution: {
        status: string;
        count: number;
        percentage: number;
    }[];
    intern_performance: {
        id: string;
        name: string;
        email: string | null;
        attended_days: number;
        missed_days: number;
        attendance_rate: number;
        total_hours: number;
        last_attended_on: string | null;
    }[];
}

export interface AttendanceRecord {
    id: string;
    date: string;
    check_in_server_time: string | null;
    check_out_server_time: string | null;
    work_duration_minutes: number | null;
    status: string;
    wifi_ssid: string | null;
    wifi_bssid: string | null;
    intern: {
        id: string | null;
        name: string | null;
        email: string | null;
        institution: string | null;
        registration_number: string | null;
        batch: {
            id: string;
            batch_code: string;
            name: string;
        } | null;
    };
}
