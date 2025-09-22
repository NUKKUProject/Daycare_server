--
-- PostgreSQL database dump
--

-- Dumped from database version 15.10 (Debian 15.10-1.pgdg120+1)
-- Dumped by pg_dump version 15.11 (Ubuntu 15.11-1.pgdg22.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: update_timestamp(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.Updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_timestamp() OWNER TO postgres;

--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_updated_at_column() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: attendance; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.attendance (
    id integer NOT NULL,
    student_id character varying(20),
    check_date timestamp without time zone NOT NULL,
    status character varying(20),
    leave_note text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_recorded boolean DEFAULT false,
    status_checkout character varying(20) DEFAULT NULL::character varying,
    check_out_time time without time zone,
    CONSTRAINT chki_status_check CHECK (((status)::text = ANY (ARRAY['present'::text, 'absent'::text, 'leave'::text, 'late'::text])))
);


ALTER TABLE public.attendance OWNER TO postgres;

--
-- Name: COLUMN attendance.leave_note; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.attendance.leave_note IS 'บันทึกการลา';


--
-- Name: COLUMN attendance.status_checkout; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.attendance.status_checkout IS 'สถานะการเช็คเอาท์: checked_out=กลับบ้านแล้ว, null=ยังไม่ได้กลับ';


--
-- Name: COLUMN attendance.check_out_time; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.attendance.check_out_time IS 'เวลาที่เช็คเอาท์';


--
-- Name: attendance_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.attendance_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.attendance_id_seq OWNER TO postgres;

--
-- Name: attendance_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.attendance_id_seq OWNED BY public.attendance.id;


--
-- Name: children; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.children (
    id integer NOT NULL,
    studentid character varying(50),
    prefix_th character varying(50),
    firstname_th character varying(255),
    lastname_th character varying(255),
    prefix_en character varying(50),
    firstname_en character varying(255),
    lastname_en character varying(255),
    id_card character varying(13),
    issue_at character varying(255),
    issue_date date,
    expiry_date date,
    race character varying(50),
    nationality character varying(50),
    religion character varying(50),
    age_student integer,
    birthday date,
    place_birth character varying(255),
    height numeric(5,2),
    weight numeric(5,2),
    sex character varying(10),
    congenital_disease text,
    profile_image character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    father_first_name character varying(50),
    father_last_name character varying(50),
    mother_first_name character varying(50),
    mother_last_name character varying(50),
    classroom character varying(50),
    father_phone character varying(15),
    mother_phone character varying(15),
    nickname character varying(50),
    child_group character varying(255) DEFAULT NULL::character varying,
    qr_code text,
    blood_type character varying(5),
    allergic_food text,
    allergic_medicine text,
    address text,
    district character varying(100),
    amphoe character varying(100),
    province character varying(100),
    zipcode character varying(5),
    emergency_contact character varying(100),
    emergency_phone character varying(20),
    emergency_relation character varying(50),
    father_phone_backup character varying(20),
    mother_phone_backup character varying(20),
    academic_year integer,
    status character varying(50) DEFAULT 'กำลังศึกษา'::character varying,
    CONSTRAINT children_height_check CHECK ((height >= (0)::numeric)),
    CONSTRAINT children_sex_check CHECK (((sex)::text = ANY (ARRAY[('ชาย'::character varying)::text, ('หญิง'::character varying)::text, ('อื่นๆ'::character varying)::text]))),
    CONSTRAINT children_weight_check CHECK ((weight >= (0)::numeric))
);


ALTER TABLE public.children OWNER TO postgres;

--
-- Name: COLUMN children.blood_type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.blood_type IS 'กรุ๊ปเลือด';


--
-- Name: COLUMN children.allergic_food; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.allergic_food IS 'อาหารที่แพ้';


--
-- Name: COLUMN children.allergic_medicine; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.allergic_medicine IS 'ยาที่แพ้';


--
-- Name: COLUMN children.address; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.address IS 'ที่อยู่';


--
-- Name: COLUMN children.district; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.district IS 'ตำบล/แขวง';


--
-- Name: COLUMN children.amphoe; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.amphoe IS 'อำเภอ/เขต';


--
-- Name: COLUMN children.province; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.province IS 'จังหวัด';


--
-- Name: COLUMN children.zipcode; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.zipcode IS 'รหัสไปรษณีย์';


--
-- Name: COLUMN children.emergency_contact; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.emergency_contact IS 'ชื่อผู้ติดต่อฉุกเฉิน';


--
-- Name: COLUMN children.emergency_phone; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.emergency_phone IS 'เบอร์โทรฉุกเฉิน';


--
-- Name: COLUMN children.emergency_relation; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.children.emergency_relation IS 'ความสัมพันธ์กับผู้ติดต่อฉุกเฉิน';


--
-- Name: children_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.children_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.children_id_seq OWNER TO postgres;

--
-- Name: children_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.children_id_seq OWNED BY public.children.id;


--
-- Name: classrooms; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.classrooms (
    classroom_id integer NOT NULL,
    classroom_name character varying(50) NOT NULL,
    child_group character varying(50) NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.classrooms OWNER TO postgres;

--
-- Name: classrooms_classroom_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.classrooms_classroom_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.classrooms_classroom_id_seq OWNER TO postgres;

--
-- Name: classrooms_classroom_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.classrooms_classroom_id_seq OWNED BY public.classrooms.classroom_id;


--
-- Name: drug_allergies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.drug_allergies (
    id integer NOT NULL,
    student_id character varying(20),
    drug_name text NOT NULL,
    detection_method character varying(50) NOT NULL,
    symptoms text NOT NULL,
    has_allergy_card boolean NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.drug_allergies OWNER TO postgres;

--
-- Name: drug_allergies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.drug_allergies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.drug_allergies_id_seq OWNER TO postgres;

--
-- Name: drug_allergies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.drug_allergies_id_seq OWNED BY public.drug_allergies.id;


--
-- Name: food_allergies; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.food_allergies (
    id integer NOT NULL,
    student_id character varying(20) NOT NULL,
    food_name character varying(255) NOT NULL,
    detection_method character varying(50) NOT NULL,
    digestive_symptoms text[],
    skin_symptoms text[],
    respiratory_symptoms text[],
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.food_allergies OWNER TO postgres;

--
-- Name: food_allergies_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.food_allergies_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.food_allergies_id_seq OWNER TO postgres;

--
-- Name: food_allergies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.food_allergies_id_seq OWNED BY public.food_allergies.id;


--
-- Name: growth_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.growth_records (
    id integer NOT NULL,
    student_id character varying(20) NOT NULL,
    age_year integer NOT NULL,
    age_month integer NOT NULL,
    age_day integer NOT NULL,
    weight numeric(5,2),
    height numeric(5,2),
    head_circumference numeric(5,2),
    is_draft boolean DEFAULT false,
    age_range character varying(10),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    child_group character varying(255),
    sex character varying(10),
    gm_issue integer,
    fm_issue integer,
    rl_issue integer,
    el_issue integer,
    ps_issue integer,
    gm_status character varying(10),
    fm_status character varying(10),
    rl_status character varying(10),
    el_status character varying(10),
    ps_status character varying(10)
);


ALTER TABLE public.growth_records OWNER TO postgres;

--
-- Name: growth_records_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.growth_records_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.growth_records_id_seq OWNER TO postgres;

--
-- Name: growth_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.growth_records_id_seq OWNED BY public.growth_records.id;


--
-- Name: health_data; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.health_data (
    id integer NOT NULL,
    student_id character varying(20) NOT NULL,
    prefix_th character varying(10),
    first_name_th character varying(100),
    last_name_th character varying(100),
    child_group character varying(50),
    classroom character varying(50),
    hair json,
    eye json,
    mouth json,
    teeth json,
    ears json,
    nose json,
    nails json,
    skin json,
    hands_feet json,
    arms_legs json,
    body json,
    symptoms json,
    medicine json,
    illness_reason text,
    accident_reason text,
    teacher_note text,
    teacher_signature character varying(200),
    created_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone,
    eye_condition character varying(50),
    nose_condition character varying(50),
    teeth_count integer,
    fever_temp numeric(4,1),
    cough_type character varying(50),
    skin_wound_detail text,
    skin_rash_detail text,
    medicine_detail text,
    hair_reason text,
    eye_reason text,
    nose_reason text,
    symptoms_reason text,
    medicine_reason text
);


ALTER TABLE public.health_data OWNER TO postgres;

--
-- Name: health_data_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.health_data_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.health_data_id_seq OWNER TO postgres;

--
-- Name: health_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.health_data_id_seq OWNED BY public.health_data.id;


--
-- Name: images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.images (
    id integer NOT NULL,
    file_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    upload_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.images OWNER TO postgres;

--
-- Name: images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.images_id_seq OWNER TO postgres;

--
-- Name: images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.images_id_seq OWNED BY public.images.id;


--
-- Name: nutrition_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.nutrition_records (
    id integer NOT NULL,
    student_id character varying(50) NOT NULL,
    weight numeric(5,2),
    height numeric(5,2),
    meal_type character varying(20) NOT NULL,
    meal_status character varying(50) NOT NULL,
    nutrition_note text,
    recorded_by integer NOT NULL,
    recorded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.nutrition_records OWNER TO postgres;

--
-- Name: nutrition_records_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.nutrition_records_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.nutrition_records_id_seq OWNER TO postgres;

--
-- Name: nutrition_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.nutrition_records_id_seq OWNED BY public.nutrition_records.id;


--
-- Name: student_transitions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.student_transitions (
    id integer NOT NULL,
    child_id integer NOT NULL,
    academic_year integer NOT NULL,
    current_class_level character varying(50) NOT NULL,
    current_classroom character varying(50) NOT NULL,
    new_class_level character varying(50) NOT NULL,
    new_classroom character varying(50) NOT NULL,
    transition_type character varying(50) NOT NULL,
    status_id integer,
    reason text,
    effective_date date,
    created_by integer NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.student_transitions OWNER TO postgres;

--
-- Name: student_transitions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.student_transitions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.student_transitions_id_seq OWNER TO postgres;

--
-- Name: student_transitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.student_transitions_id_seq OWNED BY public.student_transitions.id;


--
-- Name: teachers; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.teachers (
    id integer NOT NULL,
    teacher_id integer NOT NULL,
    first_name character varying(50),
    last_name character varying(50),
    email character varying(100),
    phone_number character varying(20),
    classroom_ids character varying,
    group_ids character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    profile_image text
);


ALTER TABLE public.teachers OWNER TO postgres;

--
-- Name: teachers_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.teachers_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.teachers_id_seq OWNER TO postgres;

--
-- Name: teachers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.teachers_id_seq OWNED BY public.teachers.id;


--
-- Name: transition_statuses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transition_statuses (
    id integer NOT NULL,
    status_name character varying(50) NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.transition_statuses OWNER TO postgres;

--
-- Name: transition_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transition_statuses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transition_statuses_id_seq OWNER TO postgres;

--
-- Name: transition_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transition_statuses_id_seq OWNED BY public.transition_statuses.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    password text NOT NULL,
    role character varying(20) NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    studentid character varying(20)
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vaccine_age_groups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vaccine_age_groups (
    id integer NOT NULL,
    age_group character varying(50) NOT NULL,
    display_order integer NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.vaccine_age_groups OWNER TO postgres;

--
-- Name: vaccine_age_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vaccine_age_groups_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.vaccine_age_groups_id_seq OWNER TO postgres;

--
-- Name: vaccine_age_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vaccine_age_groups_id_seq OWNED BY public.vaccine_age_groups.id;


--
-- Name: vaccine_list; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vaccine_list (
    id integer NOT NULL,
    age_group_id integer,
    vaccine_name character varying(255) NOT NULL,
    vaccine_description text,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.vaccine_list OWNER TO postgres;

--
-- Name: vaccine_list_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vaccine_list_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.vaccine_list_id_seq OWNER TO postgres;

--
-- Name: vaccine_list_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vaccine_list_id_seq OWNED BY public.vaccine_list.id;


--
-- Name: vaccines; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vaccines (
    id integer NOT NULL,
    student_id character varying(20) NOT NULL,
    vaccine_date date NOT NULL,
    vaccine_name character varying(255) NOT NULL,
    vaccine_number integer NOT NULL,
    vaccine_location character varying(255) NOT NULL,
    vaccine_provider character varying(255) NOT NULL,
    lot_number character varying(100),
    next_appointment date,
    vaccine_note text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    vaccine_list_id integer,
    status character varying(20) DEFAULT 'pending'::character varying,
    image_path character varying(255)
);


ALTER TABLE public.vaccines OWNER TO postgres;

--
-- Name: vaccines_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.vaccines_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.vaccines_id_seq OWNER TO postgres;

--
-- Name: vaccines_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.vaccines_id_seq OWNED BY public.vaccines.id;


--
-- Name: attendance id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance ALTER COLUMN id SET DEFAULT nextval('public.attendance_id_seq'::regclass);


--
-- Name: children id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.children ALTER COLUMN id SET DEFAULT nextval('public.children_id_seq'::regclass);


--
-- Name: classrooms classroom_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.classrooms ALTER COLUMN classroom_id SET DEFAULT nextval('public.classrooms_classroom_id_seq'::regclass);


--
-- Name: drug_allergies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drug_allergies ALTER COLUMN id SET DEFAULT nextval('public.drug_allergies_id_seq'::regclass);


--
-- Name: food_allergies id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.food_allergies ALTER COLUMN id SET DEFAULT nextval('public.food_allergies_id_seq'::regclass);


--
-- Name: growth_records id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.growth_records ALTER COLUMN id SET DEFAULT nextval('public.growth_records_id_seq'::regclass);


--
-- Name: health_data id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.health_data ALTER COLUMN id SET DEFAULT nextval('public.health_data_id_seq'::regclass);


--
-- Name: images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.images ALTER COLUMN id SET DEFAULT nextval('public.images_id_seq'::regclass);


--
-- Name: nutrition_records id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nutrition_records ALTER COLUMN id SET DEFAULT nextval('public.nutrition_records_id_seq'::regclass);


--
-- Name: student_transitions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_transitions ALTER COLUMN id SET DEFAULT nextval('public.student_transitions_id_seq'::regclass);


--
-- Name: teachers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teachers ALTER COLUMN id SET DEFAULT nextval('public.teachers_id_seq'::regclass);


--
-- Name: transition_statuses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transition_statuses ALTER COLUMN id SET DEFAULT nextval('public.transition_statuses_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vaccine_age_groups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_age_groups ALTER COLUMN id SET DEFAULT nextval('public.vaccine_age_groups_id_seq'::regclass);


--
-- Name: vaccine_list id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_list ALTER COLUMN id SET DEFAULT nextval('public.vaccine_list_id_seq'::regclass);


--
-- Name: vaccines id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines ALTER COLUMN id SET DEFAULT nextval('public.vaccines_id_seq'::regclass);


--
-- Name: attendance attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_pkey PRIMARY KEY (id);


--
-- Name: attendance attendance_student_id_check_date_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_student_id_check_date_key UNIQUE (student_id, check_date);


--
-- Name: children children_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.children
    ADD CONSTRAINT children_pkey PRIMARY KEY (id);


--
-- Name: children children_studentid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.children
    ADD CONSTRAINT children_studentid_unique UNIQUE (studentid);


--
-- Name: classrooms classrooms_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.classrooms
    ADD CONSTRAINT classrooms_pkey PRIMARY KEY (classroom_id);


--
-- Name: drug_allergies drug_allergies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drug_allergies
    ADD CONSTRAINT drug_allergies_pkey PRIMARY KEY (id);


--
-- Name: food_allergies food_allergies_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.food_allergies
    ADD CONSTRAINT food_allergies_pkey PRIMARY KEY (id);


--
-- Name: growth_records growth_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.growth_records
    ADD CONSTRAINT growth_records_pkey PRIMARY KEY (id);


--
-- Name: health_data health_data_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.health_data
    ADD CONSTRAINT health_data_pkey PRIMARY KEY (id);


--
-- Name: images images_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.images
    ADD CONSTRAINT images_pkey PRIMARY KEY (id);


--
-- Name: nutrition_records nutrition_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nutrition_records
    ADD CONSTRAINT nutrition_records_pkey PRIMARY KEY (id);


--
-- Name: student_transitions student_transitions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_transitions
    ADD CONSTRAINT student_transitions_pkey PRIMARY KEY (id);


--
-- Name: teachers teachers_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teachers
    ADD CONSTRAINT teachers_email_key UNIQUE (email);


--
-- Name: teachers teachers_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teachers
    ADD CONSTRAINT teachers_pkey PRIMARY KEY (id);


--
-- Name: transition_statuses transition_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transition_statuses
    ADD CONSTRAINT transition_statuses_pkey PRIMARY KEY (id);


--
-- Name: attendance unique_student_date; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT unique_student_date UNIQUE (student_id, check_date);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: vaccine_age_groups vaccine_age_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_age_groups
    ADD CONSTRAINT vaccine_age_groups_pkey PRIMARY KEY (id);


--
-- Name: vaccine_list vaccine_list_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_list
    ADD CONSTRAINT vaccine_list_pkey PRIMARY KEY (id);


--
-- Name: vaccines vaccines_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines
    ADD CONSTRAINT vaccines_pkey PRIMARY KEY (id);


--
-- Name: children_id_card_key; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX children_id_card_key ON public.children USING btree (id_card) WHERE (id_card IS NOT NULL);


--
-- Name: idx_attendance_check_out_time; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_attendance_check_out_time ON public.attendance USING btree (check_out_time);


--
-- Name: idx_attendance_is_recorded; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_attendance_is_recorded ON public.attendance USING btree (is_recorded);


--
-- Name: idx_attendance_status_checkout; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_attendance_status_checkout ON public.attendance USING btree (status_checkout);


--
-- Name: idx_attendance_student_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_attendance_student_date ON public.attendance USING btree (student_id, check_date);


--
-- Name: idx_student_transitions_academic_year; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_student_transitions_academic_year ON public.student_transitions USING btree (academic_year);


--
-- Name: idx_student_transitions_child_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_student_transitions_child_id ON public.student_transitions USING btree (child_id);


--
-- Name: attendance update_attendance_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_attendance_updated_at BEFORE UPDATE ON public.attendance FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: children update_children_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_children_timestamp BEFORE UPDATE ON public.children FOR EACH ROW EXECUTE FUNCTION public.update_timestamp();


--
-- Name: growth_records update_growth_records_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_growth_records_updated_at BEFORE UPDATE ON public.growth_records FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: student_transitions update_student_transitions_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_student_transitions_updated_at BEFORE UPDATE ON public.student_transitions FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: vaccines update_vaccines_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_vaccines_updated_at BEFORE UPDATE ON public.vaccines FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: attendance attendance_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- Name: drug_allergies drug_allergies_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.drug_allergies
    ADD CONSTRAINT drug_allergies_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- Name: student_transitions fk_child; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_transitions
    ADD CONSTRAINT fk_child FOREIGN KEY (child_id) REFERENCES public.children(id);


--
-- Name: student_transitions fk_created_by; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_transitions
    ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: users fk_user_student; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT fk_user_student FOREIGN KEY (studentid) REFERENCES public.children(studentid);


--
-- Name: food_allergies food_allergies_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.food_allergies
    ADD CONSTRAINT food_allergies_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid) ON DELETE CASCADE;


--
-- Name: growth_records growth_records_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.growth_records
    ADD CONSTRAINT growth_records_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- Name: nutrition_records nutrition_records_recorded_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nutrition_records
    ADD CONSTRAINT nutrition_records_recorded_by_fkey FOREIGN KEY (recorded_by) REFERENCES public.users(id);


--
-- Name: nutrition_records nutrition_records_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.nutrition_records
    ADD CONSTRAINT nutrition_records_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- Name: student_transitions student_transitions_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.student_transitions
    ADD CONSTRAINT student_transitions_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.transition_statuses(id);


--
-- Name: vaccine_list vaccine_list_age_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_list
    ADD CONSTRAINT vaccine_list_age_group_id_fkey FOREIGN KEY (age_group_id) REFERENCES public.vaccine_age_groups(id);


--
-- Name: vaccine_list vaccine_list_age_group_id_fkey1; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccine_list
    ADD CONSTRAINT vaccine_list_age_group_id_fkey1 FOREIGN KEY (age_group_id) REFERENCES public.vaccine_age_groups(id);


--
-- Name: vaccines vaccines_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines
    ADD CONSTRAINT vaccines_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- Name: vaccines vaccines_vaccine_list_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines
    ADD CONSTRAINT vaccines_vaccine_list_id_fkey FOREIGN KEY (vaccine_list_id) REFERENCES public.vaccine_list(id);


--
-- PostgreSQL database dump complete
--

