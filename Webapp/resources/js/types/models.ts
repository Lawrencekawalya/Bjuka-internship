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
