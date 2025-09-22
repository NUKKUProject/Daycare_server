--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

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
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

DROP TABLE IF EXISTS public.users CASCADE;

CREATE TABLE public.users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    studentid VARCHAR(20)
);

-- เพิ่มข้อมูล admin user
INSERT INTO users (username, password, role) 
VALUES (
    'admin',
    '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru',  -- รหัสผ่านคือ 'admin1234'
    'admin'
);

-- เพิ่มข้อมูล teacher users
INSERT INTO users (username, password, role) VALUES
('teacher1', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher'),
('teacher2', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher'),
('teacher3', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher'),
('teacher4', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher'),
('teacher5', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher'),
('teacher6', '$2y$10$8K1p/bkrzPWW1XZBzBT7/.vE5.9z3Bm6P1mBF0ZXKjbZMI3tgNOru', 'teacher');


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


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;
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
    student_id character varying(50),
    check_date timestamp without time zone,
    status character varying(10),
    check_out_time timestamp without time zone,
    status_checkout text DEFAULT 'no_checked_out'::character varying,
    leave_note text,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_recorded boolean DEFAULT false,
    CONSTRAINT attendance_status_check CHECK (((status)::text = ANY ((ARRAY['present'::character varying, 'absent'::character varying, 'leave'::character varying])::text[]))),
    CONSTRAINT chk_status_checkout CHECK ((status_checkout = ANY (ARRAY[('checked_out'::character varying)::text, ('no_checked_out'::character varying)::text]))),
    CONSTRAINT chki_status_check CHECK (((status)::text = ANY ((ARRAY['present'::character varying, 'absent'::character varying, 'leave'::character varying])::text[]))),
    CONSTRAINT chki_status_checkout_check CHECK ((status_checkout = ANY (ARRAY['checked_out'::text, 'no_checked_out'::text, NULL::text])))
);


ALTER TABLE public.attendance OWNER TO postgres;

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


ALTER SEQUENCE public.attendance_id_seq OWNER TO postgres;

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
    CONSTRAINT children_height_check CHECK ((height >= (0)::numeric)),
    CONSTRAINT children_sex_check CHECK (((sex)::text = ANY ((ARRAY['ชาย'::character varying, 'หญิง'::character varying, 'อื่นๆ'::character varying])::text[]))),
    CONSTRAINT children_weight_check CHECK ((weight >= (0)::numeric))
);

-- หลังจากสร้างตารางแล้ว ค่อยเพิ่มคอลัมใหม่
ALTER TABLE children
ADD COLUMN IF NOT EXISTS blood_type VARCHAR(5),
ADD COLUMN IF NOT EXISTS allergic_food TEXT,
ADD COLUMN IF NOT EXISTS allergic_medicine TEXT,
ADD COLUMN IF NOT EXISTS address TEXT,
ADD COLUMN IF NOT EXISTS district VARCHAR(100),
ADD COLUMN IF NOT EXISTS amphoe VARCHAR(100),
ADD COLUMN IF NOT EXISTS province VARCHAR(100),
ADD COLUMN IF NOT EXISTS zipcode VARCHAR(5),
ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(100),
ADD COLUMN IF NOT EXISTS emergency_phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS emergency_relation VARCHAR(50),
ADD COLUMN IF NOT EXISTS age_years INTEGER,
ADD COLUMN IF NOT EXISTS age_months INTEGER,
ADD COLUMN IF NOT EXISTS age_days INTEGER;

-- เพิ่ม comment
COMMENT ON COLUMN children.blood_type IS 'กรุ๊ปเลือด';
COMMENT ON COLUMN children.allergic_food IS 'อาหารที่แพ้';
COMMENT ON COLUMN children.allergic_medicine IS 'ยาที่แพ้';
COMMENT ON COLUMN children.address IS 'ที่อยู่';
COMMENT ON COLUMN children.district IS 'ตำบล/แขวง';
COMMENT ON COLUMN children.amphoe IS 'อำเภอ/เขต';
COMMENT ON COLUMN children.province IS 'จังหวัด';
COMMENT ON COLUMN children.zipcode IS 'รหัสไปรษณีย์';
COMMENT ON COLUMN children.emergency_contact IS 'ชื่อผู้ติดต่อฉุกเฉิน';
COMMENT ON COLUMN children.emergency_phone IS 'เบอร์โทรฉุกเฉิน';
COMMENT ON COLUMN children.emergency_relation IS 'ความสัมพันธ์กับผู้ติดต่อฉุกเฉิน';
COMMENT ON COLUMN children.age_years IS 'อายุ (ปี)';
COMMENT ON COLUMN children.age_months IS 'อายุ (เดือน)';
COMMENT ON COLUMN children.age_days IS 'อายุ (วัน)';

ALTER TABLE public.children OWNER TO postgres;


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


ALTER SEQUENCE public.children_id_seq OWNER TO postgres;

--
-- Name: children_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.children_id_seq OWNED BY public.children.id;


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


ALTER SEQUENCE public.health_data_id_seq OWNER TO postgres;

--
-- Name: health_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.health_data_id_seq OWNED BY public.health_data.id;


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


ALTER SEQUENCE public.teachers_id_seq OWNER TO postgres;

--
-- Name: teachers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.teachers_id_seq OWNED BY public.teachers.id;


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
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
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


ALTER SEQUENCE public.vaccines_id_seq OWNER TO postgres;

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
-- Name: health_data id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.health_data ALTER COLUMN id SET DEFAULT nextval('public.health_data_id_seq'::regclass);


--
-- Name: teachers id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teachers ALTER COLUMN id SET DEFAULT nextval('public.teachers_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vaccines id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines ALTER COLUMN id SET DEFAULT nextval('public.vaccines_id_seq'::regclass);


--
-- Data for Name: attendance; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.attendance (id, student_id, check_date, status, check_out_time, status_checkout, leave_note, updated_at, created_at, is_recorded) FROM stdin;
109	6633221001-1	2025-01-16 16:22:16	present	\N	no_checked_out	\N	2025-01-16 16:22:18.4045	2025-01-16 16:22:18.4045	t
110	66332210001-1	2025-01-17 10:43:15	present	\N	no_checked_out	\N	2025-01-17 10:43:16.789725	2025-01-17 10:43:16.789725	t
111	6633221001-1	2025-01-17 10:43:19	present	\N	no_checked_out	\N	2025-01-17 10:43:20.241192	2025-01-17 10:43:20.241192	t
108	66332210001-1	2025-01-16 00:00:00	leave	\N	\N	ไม่สบาย ปวดหัว	2025-01-19 14:49:56.0428	2025-01-16 16:09:11.95561	t
\.


--
-- Data for Name: children; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.children (id, studentid, prefix_th, firstname_th, lastname_th, prefix_en, firstname_en, lastname_en, id_card, issue_at, issue_date, expiry_date, race, nationality, religion, age_student, birthday, place_birth, height, weight, sex, congenital_disease, profile_image, created_at, updated_at, father_first_name, father_last_name, mother_first_name, mother_last_name, classroom, father_phone, mother_phone, nickname, child_group, qr_code) FROM stdin;
12	66332210002-0	เด็กชาย	ด็อกเตอร์	ด็อกแด๊ก	Mr.	DR.	DRtor	-	\N	\N	\N				\N	\N		0.00	0.00	ชาย		../../../public/uploads/profiles/profile_677f8f24bd2df.jpg	2024-12-12 13:54:00.064285	2025-01-14 14:54:20.398099	ไก่	ด็อกแด๊ก	น้อย	ด็อกแด๊ก	ห้อง 1/2	0455674867	0235756783	นิลตัน	เด็กโต	../../../public/uploads/qrcodes/qr_66332210002-0.png
24	66332210002-6	เด็กชาย	เจ็ก	ซาซี่	Mr.	Jek	Sasie	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	2025-01-07 10:39:22.377032	2025-01-10 10:57:22.390748	อาหวั่ง	ซาซี่	ม๊าม๋า	ซาซี่	ห้อง 1/1	0456786788	0456786748	อาตี๋	เด็กกลาง	\N
16	66332210001-3	เด็กหญิง	ไชมัสแคต	แอปเปิ้ล	Mr.	grape	apple	\N	\N	\N	\N				\N	\N		0.00	0.00	ชาย		\N	2024-12-13 13:41:32.611735	2025-01-10 10:57:22.390748	มะม่วง	แอปเปิ้ล	น้อยหน่า	แอปเปิ้ล	ห้อง 1/4	0651564622	0568465656	องุ่น	เด็กกลาง	\N
6	66332210001-6	เด็กชาย	ธนานนท์	ประชาชน	Mr.	Thananon	Pachachon	ไม่ระบุ	\N	\N	\N				\N	\N		0.00	0.00	ชาย		\N	2024-12-08 11:48:27.07553	2025-01-10 10:57:22.390748	นายธนวัต	ประชาชน	นางเพ็ญศรี	ประชาชน	ห้อง 1/2	0996655599	0665557894	ไทเกอร์5	เด็กกลาง	\N
9	66332210001-7	เด็กชาย	อานนท์	ขอนแก่น	Mr.	Rnon	khonkean	\N	\N	\N	\N				\N	\N		0.00	0.00	ชาย		\N	2024-12-09 15:58:11.938311	2025-01-10 10:57:22.390748	อาหรั่ง	ขอนแก่น	อาหร่อย	ขอนแก่น	ห้อง 1/3	0797875769	0564899559	อาไทย	เตรียมอนุบาล	\N
17	66332210001-4	เด็กหญิง	อรัญญ์	สุขใจ	Ms.	Alan	happyheart	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	2024-12-13 14:14:31.365274	2025-01-10 10:57:22.390748	นายย๋อย	สุขใจ	นางหญิง	สุขใจ	ห้อง 1/1	0561656585	0689465489	แตงไทย	เตรียมอนุบาล	\N
10	66332210001-8	เด็กชาย	นนน	สยาม	Mr.	nnn	sayam	\N	\N	\N	\N				5	\N		0.00	0.00	ชาย		\N	2024-12-11 16:01:53.658737	2025-01-10 10:57:22.390748	ไทลอย	สยาม	ทอมซิม	สยาม	ห้อง 1/1	0996251655	0865462154	หย๋อง	เตรียมอนุบาล	\N
11	66332210001-9	เด็กหญิง	นุ่นนุ่น	สว่างคาตา	Mr.	noonnoon	bright	\N	\N	\N	\N				\N	\N		0.00	0.00	ชาย		../../../public/uploads/profiles/profile_677f9033d1955.jpg	2024-12-12 10:26:04.649505	2025-01-10 11:07:00.628746	นายนัดโตะ	สว่างคาตา	เมล่อน	สว่างคาตา	ห้อง 1/3	0655489540	0268684848	นุ่น	เด็กโต	../../../public/uploads/qrcodes/qr_66332210001-9.png
3	66332210001-0	เด็กชาย	กัปตัน	กำนันหย๋อง	Mr.	captain	Gumnunyoung	1455353456735	\N	\N	\N	ไทย	ไทย	พุทธ	\N	\N		0.00	0.00	ชาย		../../../public/uploads/profiles/profile_677f827041496.jpg	2024-12-04 11:34:15.197648	2025-01-10 11:07:09.207879	นายดำ	กำนันหย๋อง	นางแดง	กำนันหย๋อง	ห้อง 1/1	0995846448	0815523358	กัปตัน	เด็กกลาง	../../../public/uploads/qrcodes/qr_66332210001-0.png
23	66332210002-5	เด็กชาย	ใหญ่	สามารถ	Mr.	Big	Sama	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	2025-01-07 10:34:45.127807	2025-01-10 11:07:16.253527	นายเล็ก	สามารถ	มด	สามารถ	ห้อง 1/1	0501648795	0960874784	บิ๊ก	เด็กกลาง	../../../public/uploads/qrcodes/qr_66332210002-5.png
5	66332210001-1	เด็กหญิง	เนโกะ	จัง	Ms.	neko	jung	1455353456736	\N	\N	\N	ไทย	ไทย	พุทธ	5	\N		123.00	0.00	ชาย		../../../public/uploads/profiles/profile_67809caeb5223.jpg	2024-12-06 10:32:41.310467	2025-01-20 00:05:45.970456					ห้อง 1/1			แมว	เด็กโต	../../../public/uploads/qrcodes/qr_66332210001-1.png
14	6633221001-1	เด็กหญิง	โลตัด	เฟรช	Ms.	Lotus	Fresh	\N	\N	\N	\N				\N	\N		0.00	0.00	ชาย		../../../public/uploads/profiles/profile_677f8f0f79196.jpg	2024-12-13 13:17:48.220697	2025-01-10 11:06:30.124945	นายต๋อง	เฟรช	นางใหญ่	เฟรช	ห้อง 1/1	0589684896	0849845890	บัว	เด็กโต	../../../public/uploads/qrcodes/qr_6633221001-1.png
15	66332210001-2	เด็กชาย	บอยประกร	บังหรั่ง	Mr.	boypakorn	bungrung	\N	\N	\N	\N				\N	\N		0.00	0.00	ชาย		../../../public/uploads/profiles/profile_677f8f79aa20c.jpg	2024-12-13 13:25:07.116204	2025-01-10 11:06:49.308116	จ่อย	บังหรั่ง	จอย	บังหรั่ง	ห้อง 1/2	0651894886	0654982988	บอย	เด็กโต	../../../public/uploads/qrcodes/qr_66332210001-2.png
\.


--
-- Data for Name: health_data; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.health_data (id, student_id, prefix_th, first_name_th, last_name_th, child_group, classroom, hair, eye, mouth, teeth, ears, nose, nails, skin, hands_feet, arms_legs, body, symptoms, medicine, illness_reason, accident_reason, teacher_note, teacher_signature, created_at, updated_at, eye_condition, nose_condition, teeth_count, fever_temp, cough_type, skin_wound_detail, skin_rash_detail, medicine_detail, hair_reason, eye_reason, nose_reason, symptoms_reason, medicine_reason) FROM stdin;
2	6633221001-1	เด็กหญิง	โลตัด	เฟรช	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]				\n                                Alice Johnson\n                            	2025-01-05 15:51:34.427576	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
4	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01"]	[]	["\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e2b\\u0e39"]	[]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e40\\u0e25\\u0e47\\u0e1a\\u0e22\\u0e32\\u0e27"]	["\\u0e21\\u0e35\\u0e1c\\u0e37\\u0e48\\u0e19"]	["\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a"]	["\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e21\\u0e35\\u0e22\\u0e32","\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: \\u0e0f\\u0e24\\"\\u0e0f\\u0e24\\"\\u0e0f"]	ฟไกฟไก	ฟไกฟไ	ฟไกฟไกฟ	Alice Johnson	2025-01-05 15:57:01.369651	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
5	6633221001-1	เด็กหญิง	โลตัด	เฟรช	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19"]	[]	["\\u0e21\\u0e35\\u0e22\\u0e32","\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: \\u0e44\\u0e01\\u0e1f\\u0e44\\u0e01\\u0e1f\\u0e44\\u0e01"]	ฟไกฟไก	กฟไกฟไก	ไกฟไกฟไก	Alice Johnson	2025-01-05 15:57:01.37311	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
6	66332210002-0	เด็กชาย	ด็อกเตอร์	ด็อกแด๊ก	เด็กโต	ห้อง 1/2	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]				Alice Johnson	2025-01-05 16:12:28.81344	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
8	6633221001-1	เด็กหญิง	โลตัด	เฟรช	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]				Alice Johnson	2025-01-05 16:14:31.783365	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
9	66332210002-0	เด็กชาย	ด็อกเตอร์	ด็อกแด๊ก	เด็กโต	ห้อง 1/2	["\\u0e1c\\u0e21\\u0e22\\u0e32\\u0e27"]	["\\u0e15\\u0e32\\u0e41\\u0e14\\u0e07"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]				Alice Johnson	2025-01-05 16:15:06.422244	2025-01-05 16:24:59.122748	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
10	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["\\u0e21\\u0e35\\u0e40\\u0e2b\\u0e32"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e21\\u0e35\\u0e01\\u0e25\\u0e34\\u0e48\\u0e19\\u0e1b\\u0e32\\u0e01","\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01"]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e2b\\u0e39"]	[]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]	["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]		ผึ้งต่อย	น้องพูดเก่งมากๆ	Alice Johnson	2025-01-07 14:22:41.432508	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
11	6633221001-1	เด็กหญิง	โลตัด	เฟรช	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e15\\u0e32\\u0e41\\u0e14\\u0e07"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e40\\u0e25\\u0e47\\u0e1a\\u0e22\\u0e32\\u0e27"]	["\\u0e21\\u0e35\\u0e1c\\u0e37\\u0e48\\u0e19"]	["\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]	["\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: \\u0e40\\u0e2a\\u0e21\\u0e2b\\u0e30\\u0e2a\\u0e35\\u0e40\\u0e02\\u0e35\\u0e22\\u0e27"]	["\\u0e21\\u0e35\\u0e22\\u0e32","\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: \\u0e22\\u0e32\\u0e41\\u0e01\\u0e49\\u0e44\\u0e2d"]	ปวดหัวเล็กน้อย			Alice Johnson	2025-01-07 14:22:41.436235	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
12	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	[]	[]	[]	[]	[]				Alice Johnson	2025-01-09 10:09:01.760189	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
13	6633221001-1	เด็กหญิง	โลตัด	เฟรช	เด็กโต	ห้อง 1/1	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e1b\\u0e01\\u0e15\\u0e34"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]	[]	[]	[]	[]	[]	[]				Alice Johnson	2025-01-09 10:09:01.761889	2025-01-09 15:36:00.499277	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
14	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e1c\\u0e21\\u0e22\\u0e32\\u0e27","\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e40\\u0e2b\\u0e32"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e15\\u0e32\\u0e41\\u0e14\\u0e07"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e01\\u0e25\\u0e34\\u0e48\\u0e19\\u0e1b\\u0e32\\u0e01","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e2b\\u0e39"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":[]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e40\\u0e25\\u0e47\\u0e1a\\u0e22\\u0e32\\u0e27"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e44\\u0e04\\u0e25"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"],"unchecked":[]}	{"checked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e22\\u0e32"]}				Alice Johnson	2025-01-10 15:55:57.082868	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
15	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e1c\\u0e21\\u0e22\\u0e32\\u0e27","\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e40\\u0e2b\\u0e32"]}	{"checked":[],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e1b\\u0e01\\u0e15\\u0e34","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e32\\u0e41\\u0e14\\u0e07"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e01\\u0e25\\u0e34\\u0e48\\u0e19\\u0e1b\\u0e32\\u0e01","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e19\\u0e1b\\u0e32\\u0e01"]}	{"checked":[],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]}	{"checked":["\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e2b\\u0e39"]}	{"checked":["\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: awdawdad"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]}	{"checked":["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e40\\u0e25\\u0e47\\u0e1a\\u0e22\\u0e32\\u0e27"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14"]}	{"checked":["\\u0e21\\u0e35\\u0e41\\u0e1c\\u0e25","\\u0e21\\u0e35\\u0e1c\\u0e37\\u0e48\\u0e19"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e2a\\u0e30\\u0e2d\\u0e32\\u0e14","\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35\\u0e02\\u0e35\\u0e49\\u0e44\\u0e04\\u0e25"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":["\\u0e1b\\u0e01\\u0e15\\u0e34"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e08\\u0e38\\u0e14\\u0e2b\\u0e23\\u0e37\\u0e2d\\u0e1c\\u0e37\\u0e48\\u0e19","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e43\\u0e2a","\\u0e44\\u0e21\\u0e48\\u0e15\\u0e38\\u0e48\\u0e21\\u0e2b\\u0e19\\u0e2d\\u0e07"]}	{"checked":[],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]}	{"checked":["\\u0e21\\u0e35\\u0e22\\u0e32","\\u0e40\\u0e1e\\u0e34\\u0e48\\u0e21\\u0e40\\u0e15\\u0e34\\u0e21: dawdawd"],"unchecked":["\\u0e44\\u0e21\\u0e48\\u0e44\\u0e21\\u0e48\\u0e21\\u0e35"]}	adwdaa	awda	wdawda	Alice Johnson	2025-01-10 16:33:04.164199	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
58	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: asdawdaw"]	[]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: awdawdawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: wadawd","มีผื่น: awdawdaw"]	["จุดหรือผื่น","ตุ่มใส"]	["ตุ่มใส"]	["จุดหรือผื่น","ตุ่มหนอง"]	["เพิ่มเติม: awdawda"]	["มียา: awdawd","เพิ่มเติม: awdawd"]	awdawd	awdawd	awdawdaw	Alice Johnson	2025-01-13 12:58:19.732562	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
51	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: awdawdawdasd"]	["ตาแดง","เพิ่มเติม: awdawdawd"]	["มีกลิ่นปาก","มีแผลในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: awdawdawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","มีผื่น","มีขี้ไคล"]	["จุดหรือผื่น"]	["ปกติ"]	["ปกติ"]	[]	["มียา","เพิ่มเติม: awdawdawd"]	awdawd	awdawd	awdawd	Alice Johnson	2025-01-13 11:24:29.710111	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
52	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ฟไกฟไกฟไ"]	["ตาแดง","เพิ่มเติม: กฟไกฟไกฟไก"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: กฟไกฟไกฟไก"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","มีผื่น","มีขี้ไคล"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส"]	["จุดหรือผื่น","ตุ่มหนอง"]	[]	["มียา","เพิ่มเติม: กฟไกฟไกฟไก"]	ฟไกฟไกฟไก	ฟไกฟไกฟไก	ฟไกฟไกฟก	Alice Johnson	2025-01-13 11:30:07.357128	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
53	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ๆกไฟไกไก"]	["ตาแดง","เพิ่มเติม: กฟไกฟไกฟไ"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: กฟไกฟไก"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","มีผื่น"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มหนอง"]	["ตุ่มใส"]	[]	["มียา","เพิ่มเติม: กฟไกฟไก"]	ฟไกฟไกฟไ	ฟไกฟไกฟไก	ฟไกฟไก	Alice Johnson	2025-01-13 11:31:52.332429	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
54	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ฟไกไฟกฟไก"]	["ตาแดง","เพิ่มเติม: ฟไกฟไกฟก"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: ฟไกฟไกฟไก"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","มีผื่น"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส"]	["เพิ่มเติม: กฟไกฟไก"]	["มียา","เพิ่มเติม: ฟไกฟไกฟไ"]	กฟไกฟกฟไ	ฟไกฟไกฟไก	ฟไกฟไก	Alice Johnson	2025-01-13 11:35:22.262301	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
55	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: awdawdaw"]	[]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: awdawdawawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","มีผื่น","มีขี้ไคล"]	["จุดหรือผื่น","ตุ่มใส"]	["จุดหรือผื่น","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส"]	["เพิ่มเติม: dawdawdawd"]	["มียา","เพิ่มเติม: awdawdawd"]	awdawd	awdawdaw	dawdawdawd	Alice Johnson	2025-01-13 11:40:32.038642	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
56	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: dawdawd"]	[]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: dawdawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: dawdawd","มีผื่น: awdawdaw","มีขี้ไคล"]	["จุดหรือผื่น","ตุ่มใส"]	["จุดหรือผื่น","ตุ่มหนอง"]	["ตุ่มใส"]	["เพิ่มเติม: awdawd"]	["มียา: dawdawdawd","เพิ่มเติม: dawdawdawd"]	awdawdaw	adwdawd	awdawdawd	Alice Johnson	2025-01-13 11:52:21.59139	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
57	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: awdawdawd"]	[]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด"]	["ไม่สะอาด","มีขี้หู"]	["เพิ่มเติม: dawdawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล","รายละเอียด: dawdawd","มีผื่น","รายละเอียด: awdawda"]	["จุดหรือผื่น","ตุ่มใส"]	["ตุ่มใส","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส"]	["เพิ่มเติม: awdawdawd"]	["มียา","รายละเอียด: awdawdawd","เพิ่มเติม: awdawdawd"]				Alice Johnson	2025-01-13 12:53:00.00026	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
59	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ฟไกฟไก"]	["ตาแดง","มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: ฟไกฟไกฟ"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด","ฟันผุ 5 ซี่"]	["ไม่สะอาด","มีขี้หู"]	["มีน้ำมูก เหลือง","เพิ่มเติม: ฟไกฟไกฟไ"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: ฟไกฟไก","มีผื่น: ฟไกฟไก"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"]	["ไอ มีเสมหะ","เพิ่มเติม: ฟไกฟไก"]	["มียา: ฟไกฟไกฟ","เพิ่มเติม: ฟไกฟไกฟ"]	ไฟกฟไกฟ	ฟไกฟไกฟก	ฟไกฟกไ	Alice Johnson	2025-01-13 13:05:33.716431	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
60	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ฟไกฟไกฟไ"]	["ตาแดง","มีขี้ตา ขวาปกติ","เพิ่มเติม: ฟไกฟไก"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["มีคราบนม\\/ไม่สะอาด","ฟันผุ 7 ซี่"]	["ไม่สะอาด","มีขี้หู"]	["มีน้ำมูก เขียว","เพิ่มเติม: ฟไกฟไกฟไก"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: ฟไกฟไกฟไ","มีผื่น: กฟไกฟไกฟ"]	["ตุ่มหนอง"]	["ตุ่มใส"]	["ปกติ"]	["ไอ ไอแห้ง","เพิ่มเติม: ฟไกฟไกฟไ"]	["มียา: ฟไกฟไก","เพิ่มเติม: ฟไกฟไก"]	ฟไกฟไกฟ	กไฟไกฟไก	ฟไกฟไกฟไ	Alice Johnson	2025-01-13 13:07:56.886118	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
61	66332210002-0	เด็กชาย	ด็อกเตอร์	ด็อกแด๊ก	เด็กโต	ห้อง 1/2	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: กฟไกฟไก"]	["ตาแดง","มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: กฟไกฟไก"]	["มีกลิ่นปาก","มีแผลในปาก"]	["ไม่สะอาด","ฟันผุ 5 ซี่"]	["ไม่สะอาด","มีขี้หู"]	["มีน้ำมูก เขียว","เพิ่มเติม: กฟไกฟไก"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: กฟไกฟไก","มีผื่น: กฟไกฟไก"]	["จุดหรือผื่น","ตุ่มใส"]	["ตุ่มใส"]	["ปกติ"]	["ไอ มีเสมหะ","เพิ่มเติม: กฟไกฟไก"]	["มียา: ฟไกฟไกฟไก","เพิ่มเติม: ฟไกฟไกฟไก"]	ฟไกฟไกฟไ	กฟไกฟไกฟก	ฟไกฟไก	Alice Johnson	2025-01-13 13:24:45.476855	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
62	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: awdadad"]	["ตาแดง","มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: dawdawd"]	["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"]	["ไม่สะอาด","ฟันผุ 7 ซี่"]	["ไม่สะอาด","มีขี้หู"]	["มีน้ำมูก เขียว","เพิ่มเติม: dawdawdawd"]	["ไม่สะอาด","เล็บยาว"]	["มีแผล: wdawdawd","มีผื่น: awdawda","มีขี้ไคล"]	["จุดหรือผื่น","ตุ่มใส"]	["ตุ่มหนอง"]	["จุดหรือผื่น"]	["มีไข้ 36.8 องศา","ไอ มีเสมหะ","เพิ่มเติม: dawdawdawd"]	["มียา: awdawdawd","เพิ่มเติม: awdawdawd"]	awdawdaw	dawdawda	awdawdawd	Alice Johnson	2025-01-13 13:34:25.024963	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
63	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	["สะอาด"]	["ปกติ"]	["สะอาด"]	["ฟันผุ 8 ซี่"]	["ไม่สะอาด","มีขี้หู"]	["สะอาด"]	["สะอาด"]	["มีแผล: adwad","มีผื่น: awdawd"]	["ปกติ"]	["ปกติ"]	["จุดหรือผื่น"]	["มีไข้ 36.6 องศา","ไอ มีเสมหะ","เพิ่มเติม: awdawda"]	["มียา: awdawda","เพิ่มเติม: awdawda"]	awdawd	awdawd	awdawd	Alice Johnson	2025-01-13 14:48:14.059089	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
67	66332210001-1	เด็กหญิง	\N	\N	เด็กโต	ห้อง 1/1	{"checked":["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: dasdasd"],"unchecked":["ไม่สะอาด"]}	{"checked":["ตาแดง","มีขี้ตา ขวาปกติ","เพิ่มเติม: asdasd"],"unchecked":["ไม่ปกติ"]}	{"checked":["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","ฟันผุ 7 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง","เพิ่มเติม: asdasda"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล: asdasda","มีผื่น: asdasda","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส"],"unchecked":["ไม่ปกติ","ไม่ตุ่มหนอง"]}	{"checked":["ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น"]}	{"checked":["จุดหรือผื่น"],"unchecked":["ไม่ปกติ","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["มีไข้ 36.8 องศา","ไอ มีเสมหะ","เพิ่มเติม: asdasdasd"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา: asdasda","เพิ่มเติม: asdasda"],"unchecked":["ไม่ไม่มี"]}	asdasd	asdasd	asdasd	Alice Johnson	2025-01-13 15:29:07.381773	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
68	66332210001-1	เด็กหญิง	\N	\N	เด็กโต	ห้อง 1/1	{"checked":["ผมยาว","ไม่สะอาด","มีเหา","เพิ่มเติม: ฟหกฟหก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ตาแดง","มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: ฟหกฟหก"],"unchecked":["ไม่ปกติ"]}	{"checked":["มีกลิ่นปาก","มีแผลในปาก"],"unchecked":["ไม่สะอาด","ไม่มีตุ่มในปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ 10 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล: ฟหกฟหก","มีผื่น: ฟหกฟหก","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส"],"unchecked":["ไม่ปกติ","ไม่ตุ่มหนอง"]}	{"checked":["มีไข้ 36.5 องศา","ไอ มีเสมหะ","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา: ฟหกฟหกฟหก","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่ไม่มี"]}	ฟหกฟหก	ฟหกฟหก	ฟฟหกฟหก	Alice Johnson	2025-01-13 15:36:15.280141	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
71	undefined	เด็กหญิง	undefined	undefined	เด็กโต	ห้อง 1/1	{"checked":["สะอาด","เพิ่มเติม: czczx"],"unchecked":["ไม่ผมยาว","ไม่ไม่สะอาด","ไม่มีเหา"]}	{"checked":["ปกติ","มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: czxcxzczxc"],"unchecked":["ไม่ตาแดง"]}	{"checked":["สะอาด"],"unchecked":["ไม่มีกลิ่นปาก","ไม่มีแผลในปาก","ไม่มีตุ่มในปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ 7 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง","เพิ่มเติม: zxczxc"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล: zxczxczc","มีผื่น: zxczxc","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส"],"unchecked":["ไม่ปกติ","ไม่ตุ่มหนอง"]}	{"checked":["ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น"]}	{"checked":["มีไข้ 36.5 องศา","ไอ มีเสมหะ","เพิ่มเติม: zxczxczxc"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา: xczxczxc","เพิ่มเติม: xczxczxc"],"unchecked":["ไม่ไม่มี"]}	asdasd	asdasdas	dasdasd	Alice Johnson	2025-01-13 15:58:38.999944	\N	เหลือง/เขียว	เหลือง	7	36.5	มีเสมหะ	\N	\N	\N	czczx	czxcxzczxc	zxczxc	zxczxczxc	xczxczxc
76	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["ผมยาว","ไม่สะอาด","เพิ่มเติม: asdasd"],"unchecked":["ไม่สะอาด","ไม่มีเหา"]}	{"checked":["มีขี้ตา ขวาปกติ","เพิ่มเติม: asdasd"],"unchecked":["ไม่ปกติ","ไม่ตาแดง"]}	{"checked":["มีแผลในปาก"],"unchecked":["ไม่สะอาด","ไม่มีกลิ่นปาก","ไม่มีตุ่มในปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ 9 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด"],"unchecked":["ไม่สะอาด","ไม่มีขี้หู"]}	{"checked":["มีน้ำมูก เหลือง","เพิ่มเติม: asdasd"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด"],"unchecked":["ไม่สะอาด","ไม่เล็บยาว"]}	{"unchecked":["ไม่สะอาด","ไม่มีแผล","ไม่มีผื่น","ไม่มีขี้ไคล"]}	{"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"unchecked":["ไม่ไม่มี","ไม่มีไข้","ไม่ไอ"]}	{"unchecked":["ไม่ไม่มี","ไม่มียา"]}				Alice Johnson	2025-01-13 16:11:32.23465	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
77	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["สะอาด","เพิ่มเติม: ฟกหกฟหก"],"unchecked":["ไม่ผมยาว","ไม่ไม่สะอาด","ไม่มีเหา"]}	{"checked":["มีขี้ตา เหลือง\\/เขียว","เพิ่มเติม: กฟหกฟหก"],"unchecked":["ไม่ปกติ","ไม่ตาแดง"]}	{"checked":["มีกลิ่นปาก","มีแผลในปาก"],"unchecked":["ไม่สะอาด","ไม่มีตุ่มในปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ 9 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล: ฟหกฟหก","มีผื่น: ฟหกฟหกฟหก","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ","ไม่จุดหรือผื่น"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["มีไข้ 36.4 องศา","ไอ มีเสมหะ","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา: ฟหกฟหกฟหก","เพิ่มเติม: ฟหกฟหกฟหก"],"unchecked":["ไม่ไม่มี"]}	ฟหกฟหกฟหก	ฟหกฟหก	ฟหกฟหกฟหก	Alice Johnson	2025-01-13 16:17:08.344063	\N	เหลือง/เขียว	เหลือง	9	36.4	มีเสมหะ	\N	\N	\N	ฟกหกฟหก	กฟหกฟหก	ฟหกฟหกฟหก	ฟหกฟหกฟหก	ฟหกฟหกฟหก
78	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["สะอาด"],"unchecked":["ไม่ผมยาว","ไม่ไม่สะอาด","ไม่มีเหา"]}	{"checked":["ตาแดง","มีขี้ตา เหลือง\\/เขียว"],"unchecked":["ไม่ปกติ"]}	{"checked":["สะอาด"],"unchecked":["ไม่มีกลิ่นปาก","ไม่มีแผลในปาก","ไม่มีตุ่มในปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ 9 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล","มีผื่น","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส"],"unchecked":["ไม่ปกติ","ไม่ตุ่มหนอง"]}	{"checked":["จุดหรือผื่น","ตุ่มใส"],"unchecked":["ไม่ปกติ","ไม่ตุ่มหนอง"]}	{"checked":["มีไข้ 36.4 องศา","ไอ มีเสมหะ"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา"],"unchecked":["ไม่ไม่มี"]}	asdasdasd	asdasdasd	asdasdas	Alice Johnson	2025-01-13 16:21:05.992958	\N	เหลือง/เขียว	เหลือง	9	36.4	มีเสมหะ	\N	\N	\N	asdasda	asdasd	asdasdasd	asdasdasd	asdasdasd
79	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["สะอาด"],"unchecked":["ไม่ผมยาว","ไม่ไม่สะอาด","ไม่มีเหา"]}	{"checked":["มีขี้ตา เหลือง\\/เขียว"],"unchecked":["ไม่ปกติ","ไม่ตาแดง"]}	{"checked":["มีกลิ่นปาก","มีแผลในปาก","มีตุ่มในปาก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","ฟันผุ 12 ซี่"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก เหลือง"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล","มีผื่น","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["มีไข้ 36.4 องศา","ไอ มีเสมหะ"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา"],"unchecked":["ไม่ไม่มี"]}	asdasdasdas	dasdasdasda	sdasdasd	Alice Johnson	2025-01-13 16:28:37.833482	\N	เหลือง/เขียว	เหลือง	12	36.4	มีเสมหะ	asdasd	asdasdasd	asdasdasd	asdasd	asdasd	asdasdasd	asdasdasd	asdasdasd
7	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["ไม่สะอาด","มีเหา"],"unchecked":["ไม่สะอาด","ไม่ผมยาว"]}	{"checked":["ปกติ"],"unchecked":["ไม่ตาแดง","ไม่มีขี้ตา"]}	{"checked":["สะอาด"],"unchecked":["ไม่มีกลิ่นปาก","ไม่มีแผลในปาก","ไม่มีตุ่มในปาก"]}	{"checked":["สะอาด"],"unchecked":["ไม่ไม่สะอาด","ไม่ฟันผุ"]}	{"checked":["สะอาด"],"unchecked":["ไม่ไม่สะอาด","ไม่มีขี้หู"]}	{"checked":["สะอาด"],"unchecked":["ไม่มีน้ำมูก"]}	{"checked":["สะอาด"],"unchecked":["ไม่ไม่สะอาด","ไม่เล็บยาว"]}	{"checked":["สะอาด"],"unchecked":["ไม่มีแผล","ไม่มีผื่น","ไม่มีขี้ไคล"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["ปกติ"],"unchecked":["ไม่จุดหรือผื่น","ไม่ตุ่มใส","ไม่ตุ่มหนอง"]}	{"checked":["ไม่มี"],"unchecked":["ไม่มีไข้","ไม่ไอ"]}	{"checked":["ไม่มี"],"unchecked":["ไม่มียา"]}	ปวดหัว	\N	\N	Alice Johnson	2025-01-05 16:14:31.782192	2025-01-16 14:31:42.282132	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
86	66332210001-1	เด็กหญิง	เนโกะ	จัง	เด็กโต	ห้อง 1/1	{"checked":["ไม่สะอาด","มีเหา"],"unchecked":["ไม่สะอาด","ไม่ผมยาว"]}	{"checked":["ตาแดง","มีขี้ตา"],"unchecked":["ไม่ปกติ"]}	{"checked":["มีแผลในปาก","มีตุ่มในปาก"],"unchecked":["ไม่สะอาด","ไม่มีกลิ่นปาก"]}	{"checked":["ไม่สะอาด","ฟันผุ"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","มีขี้หู"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีน้ำมูก"],"unchecked":["ไม่สะอาด"]}	{"checked":["ไม่สะอาด","เล็บยาว"],"unchecked":["ไม่สะอาด"]}	{"checked":["มีแผล","มีผื่น","มีขี้ไคล"],"unchecked":["ไม่สะอาด"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["จุดหรือผื่น","ตุ่มใส","ตุ่มหนอง"],"unchecked":["ไม่ปกติ"]}	{"checked":["มีไข้","ไอ"],"unchecked":["ไม่ไม่มี"]}	{"checked":["มียา"],"unchecked":["ไม่ไม่มี"]}	asdasdฟหกฟหก	asdasdฟหกฟหก	asdasdฟหกฟหก	Alice Johnson	2025-01-14 14:18:25.34696	2025-01-17 09:33:49.28987	เหลือง/เขียว	เขียว	11	38.7	ไอแห้ง	asdasdกฟหกฟหก	asdasdฟหกฟห	asdasdฟหกฟหก	asdasd	asdasdฟหกหกฟ	asdasd	asdasdฟหกฟหก	asdasdฟหกฟหก
\.


--
-- Data for Name: teachers; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.teachers (id, teacher_id, first_name, last_name, email, phone_number, classroom_ids, group_ids, created_at, profile_image) FROM stdin;
1	3	Alice	Johnson	alice.johnson@email.com	5551112222	ห้อง 1/1,ห้อง 1/2	เด็กโต	2024-12-20 09:20:30.738282	\N
2	4	Bob	Williams	bob.williams@email.com	5553334444	ห้อง 1/1,ห้อง 1/2	เตรียมอนุบาล	2024-12-20 09:20:30.738282	\N
3	5	Catherine	Brown	catherine.brown@email.com	5555556666	ห้อง 1/1,ห้อง 1/2	เด็กโต	2024-12-20 09:20:30.738282	\N
4	6	David	Clark	david.clark@email.com	5557778888	ห้อง 1/1,ห้อง 1/2	เด็กกลาง	2024-12-20 09:20:30.738282	\N
5	7	Eve	Garcia	eve.garcia@email.com	5559990000	ห้อง 1/1,ห้อง 1/2	เตรียมอนุบาล	2024-12-20 09:20:30.738282	\N
6	8	Frank	Martinez	frank.martinez@email.com	5551231234	ห้อง 1/1,ห้อง 1/2	เด็กกลาง	2024-12-20 09:20:30.738282	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, username, password, role, created_at, studentid) FROM stdin;
1	admin	$2a$06$f2f5wK54s4.0UwRZ5pa0e.ou/ZCNfg.qKZrgFkLggcK0tgRNE1/t.	admin	2024-11-28 11:12:03.120712	\N
2	parent1	$2a$06$1rRNZVzdfQ2O1CKSdpubze8klOo8FeaJbCIPUGrAFXg4o1k/Zkorm	parent	2024-11-28 11:12:03.120712	\N
3	teacher1	$2a$06$UsCodI6hNO5AKbZU0Gwg8ukTpf2o4rJPndgLPrVWs4pZZ2mNjmPzS	teacher	2024-12-20 09:09:34.184146	\N
4	teacher2	$2a$06$2H/mAVeI5D0qRRQ35.FCR.603Q2XMO1uYET1kKpl698lNygU6AO7.	teacher	2024-12-20 09:09:34.184146	\N
5	teacher3	$2a$06$ozJ7EMl7XMj/trtf2tEAieOjNAyH8qUnzMOnSSVfIzl5bw00qKBDC	teacher	2024-12-20 09:09:34.184146	\N
6	teacher4	$2a$06$sS6T24uFW2mj40Lk5P4fcu/WEO2Gd37B8TL1g1AzPnEQm6O.nE9z6	teacher	2024-12-20 09:09:34.184146	\N
7	teacher5	$2a$06$EXOKBRMYq8DpmRApP03Z/.8sIPuindTr9tB7zJtZ9MBU2HptfjJga	teacher	2024-12-20 09:09:34.184146	\N
8	teacher6	$2a$06$21Aag6aPd9Sa.tdnE1LJYOPW3X.i67oqFK1ff/eAkE..8eKQx92vq	teacher	2024-12-20 09:10:09.627112	\N
9	66332210001-1	$2a$06$vW6F4rpuy22uGetB6fNfeeDgZABSR34pDGARRXrKVrQjLXXOMdhwe	student	2025-01-06 11:56:21.652936	66332210001-1
\.


--
-- Data for Name: vaccines; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.vaccines (id, student_id, vaccine_date, vaccine_name, vaccine_number, vaccine_location, vaccine_provider, lot_number, next_appointment, vaccine_note, created_at, updated_at) FROM stdin;
1	66332210001-1	2025-01-07	วัคซีนตับอักเสบบี ครั้งที่1	1	โรงพยาบาลขอนแก่นราม 	พญ.พิมพ์นิภา	B20085456	2025-02-07	ครั้งถัดไปในช่วงอายุ 2 เดือน	2025-01-07 15:25:00.310048	2025-01-07 16:21:16.535358
\.


--
-- Name: attendance_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.attendance_id_seq', 111, true);


--
-- Name: children_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.children_id_seq', 27, true);


--
-- Name: health_data_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.health_data_id_seq', 86, true);


--
-- Name: teachers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.teachers_id_seq', 6, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 9, true);


--
-- Name: vaccines_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.vaccines_id_seq', 1, true);


--
-- Name: attendance attendance_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT attendance_pkey PRIMARY KEY (id);


--
-- Name: children children_id_card_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.children
    ADD CONSTRAINT children_id_card_key UNIQUE (id_card);


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
-- Name: health_data health_data_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.health_data
    ADD CONSTRAINT health_data_pkey PRIMARY KEY (id);


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
-- Name: vaccines vaccines_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines
    ADD CONSTRAINT vaccines_pkey PRIMARY KEY (id);


--
-- Name: children update_children_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_children_timestamp BEFORE UPDATE ON public.children FOR EACH ROW EXECUTE FUNCTION public.update_timestamp();


--
-- Name: vaccines update_vaccines_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_vaccines_updated_at BEFORE UPDATE ON public.vaccines FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();


--
-- Name: attendance fk_student; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.attendance
    ADD CONSTRAINT fk_student FOREIGN KEY (student_id) REFERENCES public.children(studentid) ON DELETE CASCADE;


--
-- Name: users fk_user_student; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT fk_user_student FOREIGN KEY (studentid) REFERENCES public.children(studentid);


--
-- Name: teachers teachers_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.teachers
    ADD CONSTRAINT teachers_user_id_fkey FOREIGN KEY (teacher_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: vaccines vaccines_student_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vaccines
    ADD CONSTRAINT vaccines_student_id_fkey FOREIGN KEY (student_id) REFERENCES public.children(studentid);


--
-- PostgreSQL database dump complete
--

