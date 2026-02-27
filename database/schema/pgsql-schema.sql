--
-- PostgreSQL database dump
--

\restrict yFeWoeuXC8mI8IdmdvarA9Sz46ipjm0Bge3JctwoQRrFo7cb4M4gAO0vEN6N9xI

-- Dumped from database version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)

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
-- Name: vector; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;


--
-- Name: EXTENSION vector; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION vector IS 'vector data type and ivfflat and hnsw access methods';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: achievement_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.achievement_user (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    achievement_id bigint NOT NULL,
    progress integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: achievement_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.achievement_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: achievement_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.achievement_user_id_seq OWNED BY public.achievement_user.id;


--
-- Name: achievements; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.achievements (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    is_secret boolean DEFAULT false NOT NULL,
    description text,
    image character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: achievements_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.achievements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: achievements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.achievements_id_seq OWNED BY public.achievements.id;


--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id bigint,
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: affiliate_commissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.affiliate_commissions (
    id bigint NOT NULL,
    affiliate_id bigint NOT NULL,
    referred_organization_id bigint,
    invoice_id bigint,
    amount bigint NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    description text,
    approved_at timestamp(0) without time zone,
    paid_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: affiliate_commissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.affiliate_commissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: affiliate_commissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.affiliate_commissions_id_seq OWNED BY public.affiliate_commissions.id;


--
-- Name: affiliate_payouts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.affiliate_payouts (
    id bigint NOT NULL,
    affiliate_id bigint NOT NULL,
    amount bigint NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_method character varying(255) NOT NULL,
    transaction_id character varying(255),
    notes text,
    processed_by bigint,
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: affiliate_payouts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.affiliate_payouts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: affiliate_payouts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.affiliate_payouts_id_seq OWNED BY public.affiliate_payouts.id;


--
-- Name: affiliates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.affiliates (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    affiliate_code character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    commission_rate numeric(5,2) DEFAULT '20'::numeric NOT NULL,
    payment_email character varying(255),
    payment_method character varying(255) DEFAULT 'paypal'::character varying NOT NULL,
    payment_details json,
    total_earnings bigint DEFAULT '0'::bigint NOT NULL,
    pending_earnings bigint DEFAULT '0'::bigint NOT NULL,
    paid_earnings bigint DEFAULT '0'::bigint NOT NULL,
    total_referrals integer DEFAULT 0 NOT NULL,
    successful_conversions integer DEFAULT 0 NOT NULL,
    admin_notes text,
    approved_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: affiliates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.affiliates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: affiliates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.affiliates_id_seq OWNED BY public.affiliates.id;


--
-- Name: agent_conversation_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_conversation_messages (
    id character varying(36) NOT NULL,
    conversation_id character varying(36) NOT NULL,
    user_id bigint NOT NULL,
    agent character varying(255) NOT NULL,
    role character varying(25) NOT NULL,
    content text NOT NULL,
    attachments text NOT NULL,
    tool_calls text NOT NULL,
    tool_results text NOT NULL,
    usage text NOT NULL,
    meta text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: agent_conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agent_conversations (
    id character varying(36) NOT NULL,
    user_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.alerts (
    id bigint NOT NULL,
    user_id bigint,
    siding_id bigint,
    rake_id bigint,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    body text,
    severity character varying(20) DEFAULT 'info'::character varying NOT NULL,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    resolved_at timestamp(0) without time zone,
    resolved_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.alerts_id_seq OWNED BY public.alerts.id;


--
-- Name: applied_penalties; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.applied_penalties (
    id bigint NOT NULL,
    penalty_type_id bigint NOT NULL,
    rake_id bigint NOT NULL,
    wagon_id bigint,
    quantity numeric(12,2),
    distance numeric(12,2),
    rate numeric(12,2),
    amount numeric(14,2) NOT NULL,
    meta json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: applied_penalties_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.applied_penalties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: applied_penalties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.applied_penalties_id_seq OWNED BY public.applied_penalties.id;


--
-- Name: billing_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.billing_metrics (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    date date NOT NULL,
    mrr integer DEFAULT 0 NOT NULL,
    arr integer DEFAULT 0 NOT NULL,
    new_subscriptions integer DEFAULT 0 NOT NULL,
    churned integer DEFAULT 0 NOT NULL,
    credits_purchased integer DEFAULT 0 NOT NULL,
    credits_used integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: billing_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.billing_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: billing_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.billing_metrics_id_seq OWNED BY public.billing_metrics.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'default'::character varying NOT NULL,
    _lft integer DEFAULT 0 NOT NULL,
    _rgt integer DEFAULT 0 NOT NULL,
    parent_id integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    organization_id bigint
);


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: categoryables; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categoryables (
    category_id bigint NOT NULL,
    categoryable_type character varying(255) NOT NULL,
    categoryable_id bigint NOT NULL
);


--
-- Name: changelog_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.changelog_entries (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    version character varying(255),
    type character varying(255) DEFAULT 'added'::character varying NOT NULL,
    is_published boolean DEFAULT false NOT NULL,
    released_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    organization_id bigint
);


--
-- Name: changelog_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.changelog_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: changelog_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.changelog_entries_id_seq OWNED BY public.changelog_entries.id;


--
-- Name: coal_stock; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.coal_stock (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    opening_balance_mt numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    receipt_quantity_mt numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    dispatch_quantity_mt numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    closing_balance_mt numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    as_of_date date NOT NULL,
    remarks character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: coal_stock_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.coal_stock_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: coal_stock_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.coal_stock_id_seq OWNED BY public.coal_stock.id;


--
-- Name: contact_submissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.contact_submissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    message text NOT NULL,
    status character varying(255) DEFAULT 'new'::character varying,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    organization_id bigint
);


--
-- Name: contact_submissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_submissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_submissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_submissions_id_seq OWNED BY public.contact_submissions.id;


--
-- Name: credit_packs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credit_packs (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    credits integer NOT NULL,
    bonus_credits integer DEFAULT 0 NOT NULL,
    price integer NOT NULL,
    currency character varying(3) DEFAULT 'usd'::character varying NOT NULL,
    validity_days integer,
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: credit_packs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credit_packs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credit_packs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credit_packs_id_seq OWNED BY public.credit_packs.id;


--
-- Name: credits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.credits (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    creditable_type character varying(255) NOT NULL,
    creditable_id bigint NOT NULL,
    amount integer NOT NULL,
    running_balance integer NOT NULL,
    type character varying(255) NOT NULL,
    description character varying(255),
    metadata json,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: credits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.credits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: credits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.credits_id_seq OWNED BY public.credits.id;


--
-- Name: daily_vehicle_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.daily_vehicle_entries (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    entry_date date NOT NULL,
    shift integer NOT NULL,
    e_challan_no character varying(255),
    vehicle_no character varying(255),
    gross_wt numeric(10,2),
    tare_wt numeric(10,2),
    reached_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    wb_no character varying(255),
    d_challan_no character varying(255),
    challan_mode character varying(255),
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT daily_vehicle_entries_challan_mode_check CHECK (((challan_mode)::text = ANY ((ARRAY['offline'::character varying, 'online'::character varying])::text[])))
);


--
-- Name: daily_vehicle_entries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.daily_vehicle_entries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: daily_vehicle_entries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.daily_vehicle_entries_id_seq OWNED BY public.daily_vehicle_entries.id;


--
-- Name: embedding_demos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.embedding_demos (
    id bigint NOT NULL,
    content character varying(255) NOT NULL,
    embedding public.vector(3),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: embedding_demos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.embedding_demos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: embedding_demos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.embedding_demos_id_seq OWNED BY public.embedding_demos.id;


--
-- Name: enterprise_inquiries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.enterprise_inquiries (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    company character varying(255),
    phone character varying(255),
    message text NOT NULL,
    status character varying(255) DEFAULT 'new'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: enterprise_inquiries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.enterprise_inquiries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: enterprise_inquiries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.enterprise_inquiries_id_seq OWNED BY public.enterprise_inquiries.id;


--
-- Name: experience_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.experience_audits (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    points integer NOT NULL,
    levelled_up boolean DEFAULT false NOT NULL,
    level_to integer,
    type character varying(255) NOT NULL,
    reason character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT experience_audits_type_check CHECK (((type)::text = ANY ((ARRAY['add'::character varying, 'remove'::character varying, 'reset'::character varying, 'level_up'::character varying])::text[])))
);


--
-- Name: experience_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.experience_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: experience_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.experience_audits_id_seq OWNED BY public.experience_audits.id;


--
-- Name: experiences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.experiences (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    level_id bigint NOT NULL,
    experience_points integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: experiences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.experiences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: experiences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.experiences_id_seq OWNED BY public.experiences.id;


--
-- Name: exports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.exports (
    id bigint NOT NULL,
    completed_at timestamp(0) without time zone,
    file_disk character varying(255) NOT NULL,
    file_name character varying(255),
    exporter character varying(255) NOT NULL,
    processed_rows integer DEFAULT 0 NOT NULL,
    total_rows integer NOT NULL,
    successful_rows integer DEFAULT 0 NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: exports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.exports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: exports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.exports_id_seq OWNED BY public.exports.id;


--
-- Name: failed_import_rows; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_import_rows (
    id bigint NOT NULL,
    data json NOT NULL,
    import_id bigint NOT NULL,
    validation_error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: failed_import_rows_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_import_rows_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_import_rows_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_import_rows_id_seq OWNED BY public.failed_import_rows.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: failed_payment_attempts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_payment_attempts (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    gateway character varying(255) NOT NULL,
    gateway_subscription_id character varying(255),
    attempt_number smallint DEFAULT '1'::smallint NOT NULL,
    dunning_emails_sent smallint DEFAULT '0'::smallint NOT NULL,
    failed_at timestamp(0) without time zone NOT NULL,
    last_dunning_sent_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: failed_payment_attempts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_payment_attempts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_payment_attempts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_payment_attempts_id_seq OWNED BY public.failed_payment_attempts.id;


--
-- Name: feature_segments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.feature_segments (
    id bigint NOT NULL,
    feature character varying(255) NOT NULL,
    scope character varying(255) NOT NULL,
    "values" json NOT NULL,
    active boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: feature_segments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.feature_segments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: feature_segments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.feature_segments_id_seq OWNED BY public.feature_segments.id;


--
-- Name: features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.features (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    scope character varying(255) NOT NULL,
    value text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: features_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.features_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: features_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.features_id_seq OWNED BY public.features.id;


--
-- Name: flags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.flags (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    flaggable_type character varying(255) NOT NULL,
    flaggable_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: flags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.flags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: flags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.flags_id_seq OWNED BY public.flags.id;


--
-- Name: freight_rate_master; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.freight_rate_master (
    id bigint NOT NULL,
    commodity_code character varying(20) NOT NULL,
    commodity_name character varying(255) NOT NULL,
    class_code character varying(20) NOT NULL,
    risk_rate character varying(50),
    distance_from_km numeric(10,2) NOT NULL,
    distance_to_km numeric(10,2) NOT NULL,
    rate_per_mt numeric(10,2) NOT NULL,
    gst_percent numeric(5,2) DEFAULT '5'::numeric NOT NULL,
    effective_from date NOT NULL,
    effective_to date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: freight_rate_master_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.freight_rate_master_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: freight_rate_master_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.freight_rate_master_id_seq OWNED BY public.freight_rate_master.id;


--
-- Name: gateway_products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.gateway_products (
    id bigint NOT NULL,
    payment_gateway_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    gateway_product_id character varying(255) NOT NULL,
    gateway_price_id character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: gateway_products_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.gateway_products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: gateway_products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.gateway_products_id_seq OWNED BY public.gateway_products.id;


--
-- Name: guard_inspections; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.guard_inspections (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    inspection_start_time timestamp(0) without time zone,
    inspection_end_time timestamp(0) without time zone,
    movement_permission_time timestamp(0) without time zone,
    is_approved boolean DEFAULT false NOT NULL,
    remarks text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: guard_inspections_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.guard_inspections_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: guard_inspections_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.guard_inspections_id_seq OWNED BY public.guard_inspections.id;


--
-- Name: help_articles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.help_articles (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    excerpt text,
    content text NOT NULL,
    category character varying(255),
    views integer DEFAULT 0 NOT NULL,
    helpful_count integer DEFAULT 0 NOT NULL,
    not_helpful_count integer DEFAULT 0 NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    is_published boolean DEFAULT false NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    organization_id bigint
);


--
-- Name: help_articles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.help_articles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: help_articles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.help_articles_id_seq OWNED BY public.help_articles.id;


--
-- Name: imports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.imports (
    id bigint NOT NULL,
    completed_at timestamp(0) without time zone,
    file_name character varying(255) NOT NULL,
    file_path character varying(255) NOT NULL,
    importer character varying(255) NOT NULL,
    processed_rows integer DEFAULT 0 NOT NULL,
    total_rows integer NOT NULL,
    successful_rows integer DEFAULT 0 NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: imports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.imports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: imports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.imports_id_seq OWNED BY public.imports.id;


--
-- Name: indents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.indents (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    indent_number character varying(20),
    demanded_stock character varying(100),
    total_units integer,
    target_quantity_mt numeric(12,2),
    allocated_quantity_mt numeric(12,2),
    available_stock_mt numeric(12,2),
    indent_date timestamp(0) without time zone,
    indent_time timestamp(0) without time zone,
    expected_loading_date date,
    required_by_date timestamp(0) without time zone,
    railway_reference_no character varying(100),
    e_demand_reference_id character varying(100),
    fnr_number character varying(50),
    state character varying(255),
    remarks text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: indents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.indents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: indents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.indents_id_seq OWNED BY public.indents.id;


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    billable_type character varying(255) NOT NULL,
    billable_id bigint NOT NULL,
    number character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    currency character varying(3) DEFAULT 'usd'::character varying NOT NULL,
    subtotal integer NOT NULL,
    tax integer DEFAULT 0 NOT NULL,
    total integer NOT NULL,
    paid_at timestamp(0) without time zone,
    due_date date,
    line_items json,
    billing_address json,
    payment_gateway_id bigint,
    gateway_invoice_id character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: invoices_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.invoices_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: invoices_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.invoices_id_seq OWNED BY public.invoices.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: lemon_squeezy_customers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lemon_squeezy_customers (
    id bigint NOT NULL,
    billable_id bigint NOT NULL,
    billable_type character varying(255) NOT NULL,
    lemon_squeezy_id character varying(255),
    trial_ends_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lemon_squeezy_customers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lemon_squeezy_customers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemon_squeezy_customers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lemon_squeezy_customers_id_seq OWNED BY public.lemon_squeezy_customers.id;


--
-- Name: lemon_squeezy_license_key_instances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lemon_squeezy_license_key_instances (
    id bigint NOT NULL,
    identifier uuid NOT NULL,
    license_key_id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lemon_squeezy_license_key_instances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lemon_squeezy_license_key_instances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemon_squeezy_license_key_instances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lemon_squeezy_license_key_instances_id_seq OWNED BY public.lemon_squeezy_license_key_instances.id;


--
-- Name: lemon_squeezy_license_keys; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lemon_squeezy_license_keys (
    id bigint NOT NULL,
    lemon_squeezy_id character varying(255) NOT NULL,
    license_key character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    order_id character varying(255) NOT NULL,
    product_id character varying(255) NOT NULL,
    disabled boolean NOT NULL,
    activation_limit integer,
    instances_count integer NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lemon_squeezy_license_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lemon_squeezy_license_keys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemon_squeezy_license_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lemon_squeezy_license_keys_id_seq OWNED BY public.lemon_squeezy_license_keys.id;


--
-- Name: lemon_squeezy_orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lemon_squeezy_orders (
    id bigint NOT NULL,
    billable_type character varying(255) NOT NULL,
    billable_id bigint NOT NULL,
    lemon_squeezy_id character varying(255) NOT NULL,
    customer_id character varying(255) NOT NULL,
    identifier uuid NOT NULL,
    product_id character varying(255) NOT NULL,
    variant_id character varying(255) NOT NULL,
    order_number integer NOT NULL,
    currency character varying(255) NOT NULL,
    subtotal integer NOT NULL,
    discount_total integer NOT NULL,
    tax integer NOT NULL,
    total integer NOT NULL,
    tax_name character varying(255),
    status character varying(255) NOT NULL,
    receipt_url character varying(255),
    refunded boolean NOT NULL,
    refunded_at timestamp(0) without time zone,
    ordered_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lemon_squeezy_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lemon_squeezy_orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemon_squeezy_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lemon_squeezy_orders_id_seq OWNED BY public.lemon_squeezy_orders.id;


--
-- Name: lemon_squeezy_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lemon_squeezy_subscriptions (
    id bigint NOT NULL,
    billable_type character varying(255) NOT NULL,
    billable_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    lemon_squeezy_id character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    product_id character varying(255) NOT NULL,
    variant_id character varying(255) NOT NULL,
    card_brand character varying(255),
    card_last_four character varying(255),
    pause_mode character varying(255),
    pause_resumes_at timestamp(0) without time zone,
    trial_ends_at timestamp(0) without time zone,
    renews_at timestamp(0) without time zone,
    ends_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: lemon_squeezy_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lemon_squeezy_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lemon_squeezy_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lemon_squeezy_subscriptions_id_seq OWNED BY public.lemon_squeezy_subscriptions.id;


--
-- Name: levels; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.levels (
    id bigint NOT NULL,
    level integer NOT NULL,
    next_level_experience integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: levels_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.levels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: levels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.levels_id_seq OWNED BY public.levels.id;


--
-- Name: loader_performances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loader_performances (
    id bigint NOT NULL,
    loader_id bigint NOT NULL,
    as_of_date date NOT NULL,
    rakes_processed integer DEFAULT 0 NOT NULL,
    average_loading_time_minutes integer DEFAULT 0 NOT NULL,
    consistency_variance_minutes integer DEFAULT 0 NOT NULL,
    overload_incidents integer DEFAULT 0 NOT NULL,
    quality_score integer DEFAULT 100 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: loader_performances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loader_performances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loader_performances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loader_performances_id_seq OWNED BY public.loader_performances.id;


--
-- Name: loaders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.loaders (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    loader_name character varying(255) NOT NULL,
    code character varying(10) NOT NULL,
    loader_type character varying(255) NOT NULL,
    make_model character varying(255),
    last_calibration_date date,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: loaders_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.loaders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: loaders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.loaders_id_seq OWNED BY public.loaders.id;


--
-- Name: mail_exceptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mail_exceptions (
    id bigint NOT NULL,
    mail_template_id bigint NOT NULL,
    data json NOT NULL,
    type character varying(255) NOT NULL,
    code character varying(255) NOT NULL,
    message text NOT NULL,
    file character varying(255) NOT NULL,
    line integer NOT NULL,
    preview json NOT NULL,
    trace text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mail_exceptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mail_exceptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mail_exceptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mail_exceptions_id_seq OWNED BY public.mail_exceptions.id;


--
-- Name: mail_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mail_templates (
    id bigint NOT NULL,
    event character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    body text NOT NULL,
    meta json,
    recipients json NOT NULL,
    attachments json NOT NULL,
    delay character varying(255),
    is_active boolean NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: mail_templates_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.mail_templates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mail_templates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.mail_templates_id_seq OWNED BY public.mail_templates.id;


--
-- Name: media; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.media (
    id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid uuid,
    collection_name character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(255),
    disk character varying(255) NOT NULL,
    conversions_disk character varying(255),
    size bigint NOT NULL,
    manipulations json NOT NULL,
    custom_properties json NOT NULL,
    generated_conversions json NOT NULL,
    responsive_images json NOT NULL,
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: media_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.media_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: media_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.media_id_seq OWNED BY public.media.id;


--
-- Name: memories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.memories (
    id bigint NOT NULL,
    user_id character varying(255),
    content text NOT NULL,
    embedding public.vector(1536) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: memories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.memories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: memories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.memories_id_seq OWNED BY public.memories.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_flags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_flags (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    flaggable_type character varying(255) NOT NULL,
    flaggable_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: model_flags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.model_flags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: model_flags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.model_flags_id_seq OWNED BY public.model_flags.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    organization_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    organization_id bigint NOT NULL
);


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data json NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: organization_domains; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organization_domains (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    domain character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    is_verified boolean DEFAULT false NOT NULL,
    verification_token character varying(255),
    is_primary boolean DEFAULT false NOT NULL,
    verified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT organization_domains_type_check CHECK (((type)::text = ANY ((ARRAY['subdomain'::character varying, 'custom'::character varying])::text[])))
);


--
-- Name: organization_domains_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organization_domains_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organization_domains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.organization_domains_id_seq OWNED BY public.organization_domains.id;


--
-- Name: organization_invitations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organization_invitations (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(255) DEFAULT 'member'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    token character varying(64) NOT NULL,
    invited_by bigint NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    accepted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: organization_invitations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organization_invitations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organization_invitations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.organization_invitations_id_seq OWNED BY public.organization_invitations.id;


--
-- Name: organization_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organization_user (
    organization_id bigint NOT NULL,
    user_id bigint NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    joined_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    invited_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: organizations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organizations (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    settings json,
    owner_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    deleted_at timestamp(0) without time zone,
    deleted_by bigint,
    billing_email character varying(255),
    tax_id character varying(255),
    billing_address json,
    stripe_customer_id character varying(255),
    paddle_customer_id character varying(255)
);


--
-- Name: organizations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organizations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organizations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.organizations_id_seq OWNED BY public.organizations.id;


--
-- Name: pan_analytics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pan_analytics (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    impressions bigint DEFAULT '0'::bigint NOT NULL,
    hovers bigint DEFAULT '0'::bigint NOT NULL,
    clicks bigint DEFAULT '0'::bigint NOT NULL
);


--
-- Name: pan_analytics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pan_analytics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pan_analytics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pan_analytics_id_seq OWNED BY public.pan_analytics.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payment_gateways; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payment_gateways (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    settings text,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    supported_currencies json,
    supported_payment_methods json,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: payment_gateways_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.payment_gateways_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: payment_gateways_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.payment_gateways_id_seq OWNED BY public.payment_gateways.id;


--
-- Name: penalties; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penalties (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    penalty_type character varying(50) NOT NULL,
    penalty_amount numeric(12,2) NOT NULL,
    penalty_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    description text,
    remediation_notes text,
    penalty_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    calculation_breakdown json,
    responsible_party character varying(30),
    root_cause text,
    disputed_at timestamp(0) without time zone,
    dispute_reason text,
    resolved_at timestamp(0) without time zone,
    resolution_notes text,
    root_cause_category character varying(50),
    is_preventable boolean,
    suggested_remediation text
);


--
-- Name: penalties_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penalties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penalties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penalties_id_seq OWNED BY public.penalties.id;


--
-- Name: penalty_predictions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penalty_predictions (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    prediction_date date NOT NULL,
    risk_level character varying(10) NOT NULL,
    predicted_types json,
    predicted_amount_min numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    predicted_amount_max numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    factors json,
    recommendations json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: penalty_predictions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penalty_predictions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penalty_predictions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penalty_predictions_id_seq OWNED BY public.penalty_predictions.id;


--
-- Name: penalty_types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.penalty_types (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    category character varying(255) NOT NULL,
    description text,
    calculation_type character varying(255) NOT NULL,
    default_rate numeric(12,2),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT penalty_types_category_check CHECK (((category)::text = ANY ((ARRAY['overloading'::character varying, 'time_service'::character varying, 'operational'::character varying, 'safety'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: penalty_types_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.penalty_types_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: penalty_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.penalty_types_id_seq OWNED BY public.penalty_types.id;


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: plan_features; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plan_features (
    id bigint NOT NULL,
    plan_id bigint NOT NULL,
    name json NOT NULL,
    slug character varying(255) NOT NULL,
    description json,
    value character varying(255) NOT NULL,
    resettable_period smallint DEFAULT '0'::smallint NOT NULL,
    resettable_interval character varying(255) DEFAULT 'month'::character varying NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: plan_features_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plan_features_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plan_features_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plan_features_id_seq OWNED BY public.plan_features.id;


--
-- Name: plan_subscription_usage; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plan_subscription_usage (
    id bigint NOT NULL,
    subscription_id bigint NOT NULL,
    feature_id bigint NOT NULL,
    used smallint NOT NULL,
    timezone character varying(255),
    valid_until timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: plan_subscription_usage_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plan_subscription_usage_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plan_subscription_usage_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plan_subscription_usage_id_seq OWNED BY public.plan_subscription_usage.id;


--
-- Name: plan_subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plan_subscriptions (
    id bigint NOT NULL,
    subscriber_type character varying(255) NOT NULL,
    subscriber_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    name json NOT NULL,
    slug character varying(255) NOT NULL,
    description json,
    timezone character varying(255),
    trial_ends_at timestamp(0) without time zone,
    starts_at timestamp(0) without time zone,
    ends_at timestamp(0) without time zone,
    canceled_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    gateway_subscription_id character varying(255),
    quantity integer DEFAULT 1 NOT NULL
);


--
-- Name: plan_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plan_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plan_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plan_subscriptions_id_seq OWNED BY public.plan_subscriptions.id;


--
-- Name: plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plans (
    id bigint NOT NULL,
    name json NOT NULL,
    slug character varying(255) NOT NULL,
    description json,
    is_active boolean DEFAULT true NOT NULL,
    price numeric(8,2) DEFAULT 0.00 NOT NULL,
    signup_fee numeric(8,2) DEFAULT 0.00 NOT NULL,
    currency character varying(3) NOT NULL,
    trial_period smallint DEFAULT '0'::smallint NOT NULL,
    trial_interval character varying(255) DEFAULT 'day'::character varying NOT NULL,
    invoice_period smallint DEFAULT '0'::smallint NOT NULL,
    invoice_interval character varying(255) DEFAULT 'month'::character varying NOT NULL,
    grace_period smallint DEFAULT '0'::smallint NOT NULL,
    grace_interval character varying(255) DEFAULT 'day'::character varying NOT NULL,
    prorate_day smallint,
    prorate_period smallint,
    prorate_extend_due smallint,
    active_subscribers_limit smallint,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    is_per_seat boolean DEFAULT false NOT NULL,
    price_per_seat numeric(10,2) DEFAULT 0.00 NOT NULL
);


--
-- Name: plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plans_id_seq OWNED BY public.plans.id;


--
-- Name: posts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.posts (
    id bigint NOT NULL,
    author_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    excerpt text,
    content text NOT NULL,
    is_published boolean DEFAULT false NOT NULL,
    published_at timestamp(0) without time zone,
    meta_title character varying(255),
    meta_description text,
    meta_keywords character varying(255),
    views integer DEFAULT 0 NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    organization_id bigint
);


--
-- Name: posts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.posts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.posts_id_seq OWNED BY public.posts.id;


--
-- Name: power_plant_receipts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.power_plant_receipts (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    power_plant_id bigint NOT NULL,
    receipt_date date NOT NULL,
    weight_mt numeric(12,2) NOT NULL,
    rr_reference character varying(50),
    variance_mt numeric(12,2),
    variance_pct numeric(8,2),
    status character varying(30) DEFAULT 'pending'::character varying NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: power_plant_receipts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.power_plant_receipts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: power_plant_receipts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.power_plant_receipts_id_seq OWNED BY public.power_plant_receipts.id;


--
-- Name: power_plants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.power_plants (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(20) NOT NULL,
    location character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: power_plants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.power_plants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: power_plants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.power_plants_id_seq OWNED BY public.power_plants.id;


--
-- Name: rake_wagon_weighments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rake_wagon_weighments (
    id bigint NOT NULL,
    rake_weighment_id bigint NOT NULL,
    wagon_id bigint NOT NULL,
    wagon_sequence integer,
    wagon_type character varying(255),
    axles integer,
    cc_capacity_mt numeric(10,2),
    printed_tare_mt numeric(10,2),
    actual_gross_mt numeric(10,2),
    actual_tare_mt numeric(10,2),
    net_weight_mt numeric(10,2),
    under_load_mt numeric(10,2),
    over_load_mt numeric(10,2),
    speed_kmph numeric(5,2),
    weighment_time timestamp(0) without time zone,
    slip_number character varying(255),
    action_taken text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: rake_wagon_weighments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rake_wagon_weighments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rake_wagon_weighments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rake_wagon_weighments_id_seq OWNED BY public.rake_wagon_weighments.id;


--
-- Name: rake_weighments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rake_weighments (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    attempt_no integer DEFAULT 1 NOT NULL,
    gross_weighment_datetime timestamp(0) without time zone,
    tare_weighment_datetime timestamp(0) without time zone,
    train_name character varying(255),
    direction character varying(255),
    commodity character varying(255),
    from_station character varying(255),
    to_station character varying(255),
    priority_number character varying(255),
    pdf_file_path character varying(255),
    status character varying(255) DEFAULT 'success'::character varying NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: rake_weighments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rake_weighments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rake_weighments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rake_weighments_id_seq OWNED BY public.rake_weighments.id;


--
-- Name: rakes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rakes (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    indent_id bigint,
    rake_number character varying(20) NOT NULL,
    rake_type character varying(50),
    wagon_count integer,
    placement_time timestamp(0) without time zone,
    dispatch_time timestamp(0) without time zone,
    loaded_weight_mt numeric(12,2),
    predicted_weight_mt numeric(12,2),
    rr_expected_date timestamp(0) without time zone,
    rr_actual_date timestamp(0) without time zone,
    state character varying(255),
    loading_start_time timestamp(0) without time zone,
    loading_end_time timestamp(0) without time zone,
    loading_free_minutes integer DEFAULT 180 NOT NULL,
    guard_start_time timestamp(0) without time zone,
    guard_end_time timestamp(0) without time zone,
    weighment_start_time timestamp(0) without time zone,
    weighment_end_time timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint,
    deleted_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: rakes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rakes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rakes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rakes_id_seq OWNED BY public.rakes.id;


--
-- Name: referrals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.referrals (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    referral_code character varying(255) NOT NULL,
    referrer_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: referrals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.referrals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: referrals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.referrals_id_seq OWNED BY public.referrals.id;


--
-- Name: refund_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.refund_requests (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    invoice_id bigint NOT NULL,
    amount integer NOT NULL,
    reason text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    processed_at timestamp(0) without time zone,
    processed_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: refund_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.refund_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: refund_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.refund_requests_id_seq OWNED BY public.refund_requests.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    organization_id bigint,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: routes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.routes (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    route_name character varying(255) NOT NULL,
    start_location character varying(255) NOT NULL,
    end_location character varying(255) NOT NULL,
    expected_distance_km numeric(10,2) NOT NULL,
    geo_json_path_data text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: routes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.routes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: routes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.routes_id_seq OWNED BY public.routes.id;


--
-- Name: rr_documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rr_documents (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    rr_number character varying(50) NOT NULL,
    rr_received_date timestamp(0) without time zone NOT NULL,
    rr_weight_mt numeric(12,2),
    rr_details text,
    document_status character varying(255) DEFAULT 'received'::character varying NOT NULL,
    has_discrepancy boolean DEFAULT false NOT NULL,
    discrepancy_details text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    updated_by bigint,
    fnr character varying(50),
    from_station_code character varying(20),
    to_station_code character varying(20),
    freight_total numeric(14,2)
);


--
-- Name: rr_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rr_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rr_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rr_documents_id_seq OWNED BY public.rr_documents.id;


--
-- Name: rr_predictions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rr_predictions (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    predicted_weight_mt numeric(12,2) NOT NULL,
    predicted_rr_date date NOT NULL,
    prediction_confidence character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    prediction_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    variance_percent numeric(5,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: rr_predictions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rr_predictions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rr_predictions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rr_predictions_id_seq OWNED BY public.rr_predictions.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    "group" character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    locked boolean DEFAULT false NOT NULL,
    payload json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: shareables; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shareables (
    id bigint NOT NULL,
    shareable_type character varying(255) NOT NULL,
    shareable_id bigint NOT NULL,
    target_type character varying(255) NOT NULL,
    target_id bigint NOT NULL,
    permission character varying(255) DEFAULT 'view'::character varying NOT NULL,
    shared_by bigint NOT NULL,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: shareables_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shareables_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: shareables_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.shareables_id_seq OWNED BY public.shareables.id;


--
-- Name: siding_performance; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.siding_performance (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    as_of_date date NOT NULL,
    rakes_processed integer DEFAULT 0 NOT NULL,
    total_penalty_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    penalty_incidents integer DEFAULT 0 NOT NULL,
    average_demurrage_hours integer DEFAULT 0 NOT NULL,
    overload_incidents integer DEFAULT 0 NOT NULL,
    closing_stock_mt numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: siding_performance_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.siding_performance_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: siding_performance_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.siding_performance_id_seq OWNED BY public.siding_performance.id;


--
-- Name: siding_risk_scores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.siding_risk_scores (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    score smallint NOT NULL,
    risk_factors json,
    trend character varying(20) DEFAULT 'stable'::character varying NOT NULL,
    calculated_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: siding_risk_scores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.siding_risk_scores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: siding_risk_scores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.siding_risk_scores_id_seq OWNED BY public.siding_risk_scores.id;


--
-- Name: siding_user; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.siding_user (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    siding_id bigint NOT NULL,
    assigned_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    is_primary boolean DEFAULT false NOT NULL
);


--
-- Name: siding_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.siding_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: siding_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.siding_user_id_seq OWNED BY public.siding_user.id;


--
-- Name: sidings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sidings (
    id bigint NOT NULL,
    organization_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    code character varying(10) NOT NULL,
    location character varying(255) NOT NULL,
    station_code character varying(10) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    created_by bigint,
    updated_by bigint
);


--
-- Name: sidings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sidings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sidings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sidings_id_seq OWNED BY public.sidings.id;


--
-- Name: stock_ledgers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.stock_ledgers (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    transaction_type character varying(255) NOT NULL,
    vehicle_arrival_id bigint,
    rake_id bigint,
    quantity_mt numeric(10,2) NOT NULL,
    opening_balance_mt numeric(10,2) NOT NULL,
    closing_balance_mt numeric(10,2) NOT NULL,
    reference_number character varying(255),
    remarks text,
    created_by bigint,
    verified_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT stock_ledgers_transaction_type_check CHECK (((transaction_type)::text = ANY ((ARRAY['receipt'::character varying, 'dispatch'::character varying, 'correction'::character varying])::text[])))
);


--
-- Name: stock_ledgers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.stock_ledgers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: stock_ledgers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.stock_ledgers_id_seq OWNED BY public.stock_ledgers.id;


--
-- Name: streak_activities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.streak_activities (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: streak_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.streak_activities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: streak_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.streak_activities_id_seq OWNED BY public.streak_activities.id;


--
-- Name: streak_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.streak_histories (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    activity_id bigint NOT NULL,
    count integer DEFAULT 1 NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    ended_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: streak_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.streak_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: streak_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.streak_histories_id_seq OWNED BY public.streak_histories.id;


--
-- Name: streaks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.streaks (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    activity_id bigint NOT NULL,
    count integer DEFAULT 1 NOT NULL,
    activity_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    frozen_until timestamp(0) without time zone
);


--
-- Name: streaks_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.streaks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: streaks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.streaks_id_seq OWNED BY public.streaks.id;


--
-- Name: sync_queue; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sync_queue (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id character varying(255),
    payload json NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    retry_count integer DEFAULT 0 NOT NULL,
    last_attempted_at timestamp(0) without time zone,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sync_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sync_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sync_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sync_queue_id_seq OWNED BY public.sync_queue.id;


--
-- Name: taggables; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.taggables (
    tag_id bigint NOT NULL,
    taggable_type character varying(255) NOT NULL,
    taggable_id bigint NOT NULL
);


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    id bigint NOT NULL,
    name json NOT NULL,
    slug json NOT NULL,
    type character varying(255),
    order_column integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- Name: terms_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.terms_versions (
    id bigint NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    body text NOT NULL,
    type character varying(255) NOT NULL,
    effective_at date NOT NULL,
    summary text,
    is_required boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: terms_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.terms_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: terms_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.terms_versions_id_seq OWNED BY public.terms_versions.id;


--
-- Name: txr; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.txr (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    inspection_time timestamp(0) without time zone,
    inspection_end_time timestamp(0) without time zone,
    status character varying(255) DEFAULT 'in_progress'::character varying NOT NULL,
    remarks text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: txr_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.txr_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: txr_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.txr_id_seq OWNED BY public.txr.id;


--
-- Name: user_siding; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_siding (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    siding_id bigint NOT NULL,
    is_primary boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_siding_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_siding_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_siding_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_siding_id_seq OWNED BY public.user_siding.id;


--
-- Name: user_terms_acceptances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_terms_acceptances (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    terms_version_id bigint NOT NULL,
    accepted_at timestamp(0) without time zone NOT NULL,
    ip character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_terms_acceptances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_terms_acceptances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_terms_acceptances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_terms_acceptances_id_seq OWNED BY public.user_terms_acceptances.id;


--
-- Name: user_voucher; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_voucher (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    voucher_id bigint NOT NULL,
    redeemed_at timestamp(0) without time zone NOT NULL
);


--
-- Name: user_voucher_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_voucher_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_voucher_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_voucher_id_seq OWNED BY public.user_voucher.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    onboarding_completed boolean DEFAULT false NOT NULL,
    onboarding_steps_completed json,
    timezone character varying(255)
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: vehicle_arrivals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vehicle_arrivals (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    vehicle_id bigint NOT NULL,
    indent_id bigint,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    arrived_at timestamp(0) without time zone NOT NULL,
    unloading_started_at timestamp(0) without time zone,
    unloading_completed_at timestamp(0) without time zone,
    gross_weight numeric(10,2),
    tare_weight numeric(10,2),
    net_weight numeric(10,2),
    unloaded_quantity numeric(10,2),
    notes text,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    shift character varying(20),
    CONSTRAINT vehicle_arrivals_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'unloading'::character varying, 'unloaded'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: vehicle_arrivals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vehicle_arrivals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vehicle_arrivals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vehicle_arrivals_id_seq OWNED BY public.vehicle_arrivals.id;


--
-- Name: vehicle_unload_steps; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vehicle_unload_steps (
    id bigint NOT NULL,
    vehicle_unload_id bigint NOT NULL,
    step_number smallint NOT NULL,
    status character varying(255) DEFAULT 'PENDING'::character varying NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    remarks text,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vehicle_unload_steps_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vehicle_unload_steps_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vehicle_unload_steps_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vehicle_unload_steps_id_seq OWNED BY public.vehicle_unload_steps.id;


--
-- Name: vehicle_unload_weighments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vehicle_unload_weighments (
    id bigint NOT NULL,
    vehicle_unload_id bigint NOT NULL,
    gross_weight_mt numeric(8,2) NOT NULL,
    tare_weight_mt numeric(8,2),
    net_weight_mt numeric(8,2) NOT NULL,
    weighment_type character varying(30) NOT NULL,
    weighment_status character varying(30) NOT NULL,
    data_source character varying(30) DEFAULT 'MANUAL'::character varying NOT NULL,
    external_reference character varying(255),
    raw_payload json,
    weighment_time timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vehicle_unload_weighments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vehicle_unload_weighments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vehicle_unload_weighments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vehicle_unload_weighments_id_seq OWNED BY public.vehicle_unload_weighments.id;


--
-- Name: vehicle_unloads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vehicle_unloads (
    id bigint NOT NULL,
    siding_id bigint NOT NULL,
    vehicle_id bigint NOT NULL,
    jimms_challan_number character varying(30),
    arrival_time timestamp(0) without time zone NOT NULL,
    unload_start_time timestamp(0) without time zone,
    unload_end_time timestamp(0) without time zone,
    mine_weight_mt numeric(12,2),
    weighment_weight_mt numeric(12,2),
    variance_mt numeric(12,2),
    state character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    remarks character varying(255),
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    shift character varying(20),
    vehicle_arrival_id bigint
);


--
-- Name: vehicle_unloads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vehicle_unloads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vehicle_unloads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vehicle_unloads_id_seq OWNED BY public.vehicle_unloads.id;


--
-- Name: vehicles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vehicles (
    id bigint NOT NULL,
    vehicle_number character varying(20) NOT NULL,
    rfid_tag character varying(50),
    permitted_capacity_mt numeric(10,2) NOT NULL,
    tare_weight_mt numeric(10,2) NOT NULL,
    owner_name character varying(255) NOT NULL,
    vehicle_type character varying(255) NOT NULL,
    gps_device_id character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: vehicles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vehicles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vehicles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vehicles_id_seq OWNED BY public.vehicles.id;


--
-- Name: visibility_demos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.visibility_demos (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    organization_id bigint,
    visibility character varying(255) DEFAULT 'organization'::character varying NOT NULL,
    cloned_from bigint,
    title character varying(255) NOT NULL
);


--
-- Name: visibility_demos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.visibility_demos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: visibility_demos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.visibility_demos_id_seq OWNED BY public.visibility_demos.id;


--
-- Name: voucher_scopes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.voucher_scopes (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: voucher_scopes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.voucher_scopes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: voucher_scopes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.voucher_scopes_id_seq OWNED BY public.voucher_scopes.id;


--
-- Name: vouchers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.vouchers (
    id bigint NOT NULL,
    code character varying(32) NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL,
    data text,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: vouchers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.vouchers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: vouchers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.vouchers_id_seq OWNED BY public.vouchers.id;


--
-- Name: wagon_loading; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wagon_loading (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    wagon_id bigint NOT NULL,
    loader_id bigint,
    loader_operator_name character varying(255),
    cc_capacity_mt numeric(10,2),
    loaded_quantity_mt numeric(10,2),
    loading_time timestamp(0) without time zone,
    remarks text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: wagon_loading_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wagon_loading_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wagon_loading_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wagon_loading_id_seq OWNED BY public.wagon_loading.id;


--
-- Name: wagon_unfit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wagon_unfit_logs (
    id bigint NOT NULL,
    txr_id bigint NOT NULL,
    wagon_id bigint NOT NULL,
    reason text,
    marking_method character varying(255),
    marked_at timestamp(0) without time zone,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: wagon_unfit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wagon_unfit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wagon_unfit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wagon_unfit_logs_id_seq OWNED BY public.wagon_unfit_logs.id;


--
-- Name: wagons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wagons (
    id bigint NOT NULL,
    rake_id bigint NOT NULL,
    wagon_sequence integer,
    wagon_number character varying(20) NOT NULL,
    wagon_type character varying(255),
    tare_weight_mt numeric(10,2),
    pcc_weight_mt numeric(10,2),
    is_unfit boolean DEFAULT false NOT NULL,
    state character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: wagons_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.wagons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: wagons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.wagons_id_seq OWNED BY public.wagons.id;


--
-- Name: webhook_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhook_logs (
    id bigint NOT NULL,
    gateway character varying(255) NOT NULL,
    event_type character varying(255) NOT NULL,
    payload json,
    processed boolean DEFAULT false NOT NULL,
    response text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    organization_id bigint
);


--
-- Name: webhook_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.webhook_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: webhook_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.webhook_logs_id_seq OWNED BY public.webhook_logs.id;


--
-- Name: achievement_user id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievement_user ALTER COLUMN id SET DEFAULT nextval('public.achievement_user_id_seq'::regclass);


--
-- Name: achievements id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievements ALTER COLUMN id SET DEFAULT nextval('public.achievements_id_seq'::regclass);


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: affiliate_commissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_commissions ALTER COLUMN id SET DEFAULT nextval('public.affiliate_commissions_id_seq'::regclass);


--
-- Name: affiliate_payouts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_payouts ALTER COLUMN id SET DEFAULT nextval('public.affiliate_payouts_id_seq'::regclass);


--
-- Name: affiliates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliates ALTER COLUMN id SET DEFAULT nextval('public.affiliates_id_seq'::regclass);


--
-- Name: alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts ALTER COLUMN id SET DEFAULT nextval('public.alerts_id_seq'::regclass);


--
-- Name: applied_penalties id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.applied_penalties ALTER COLUMN id SET DEFAULT nextval('public.applied_penalties_id_seq'::regclass);


--
-- Name: billing_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_metrics ALTER COLUMN id SET DEFAULT nextval('public.billing_metrics_id_seq'::regclass);


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: changelog_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.changelog_entries ALTER COLUMN id SET DEFAULT nextval('public.changelog_entries_id_seq'::regclass);


--
-- Name: coal_stock id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coal_stock ALTER COLUMN id SET DEFAULT nextval('public.coal_stock_id_seq'::regclass);


--
-- Name: contact_submissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_submissions ALTER COLUMN id SET DEFAULT nextval('public.contact_submissions_id_seq'::regclass);


--
-- Name: credit_packs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_packs ALTER COLUMN id SET DEFAULT nextval('public.credit_packs_id_seq'::regclass);


--
-- Name: credits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits ALTER COLUMN id SET DEFAULT nextval('public.credits_id_seq'::regclass);


--
-- Name: daily_vehicle_entries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries ALTER COLUMN id SET DEFAULT nextval('public.daily_vehicle_entries_id_seq'::regclass);


--
-- Name: embedding_demos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.embedding_demos ALTER COLUMN id SET DEFAULT nextval('public.embedding_demos_id_seq'::regclass);


--
-- Name: enterprise_inquiries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.enterprise_inquiries ALTER COLUMN id SET DEFAULT nextval('public.enterprise_inquiries_id_seq'::regclass);


--
-- Name: experience_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experience_audits ALTER COLUMN id SET DEFAULT nextval('public.experience_audits_id_seq'::regclass);


--
-- Name: experiences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experiences ALTER COLUMN id SET DEFAULT nextval('public.experiences_id_seq'::regclass);


--
-- Name: exports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exports ALTER COLUMN id SET DEFAULT nextval('public.exports_id_seq'::regclass);


--
-- Name: failed_import_rows id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_import_rows ALTER COLUMN id SET DEFAULT nextval('public.failed_import_rows_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: failed_payment_attempts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_payment_attempts ALTER COLUMN id SET DEFAULT nextval('public.failed_payment_attempts_id_seq'::regclass);


--
-- Name: feature_segments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feature_segments ALTER COLUMN id SET DEFAULT nextval('public.feature_segments_id_seq'::regclass);


--
-- Name: features id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.features ALTER COLUMN id SET DEFAULT nextval('public.features_id_seq'::regclass);


--
-- Name: flags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flags ALTER COLUMN id SET DEFAULT nextval('public.flags_id_seq'::regclass);


--
-- Name: freight_rate_master id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freight_rate_master ALTER COLUMN id SET DEFAULT nextval('public.freight_rate_master_id_seq'::regclass);


--
-- Name: gateway_products id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gateway_products ALTER COLUMN id SET DEFAULT nextval('public.gateway_products_id_seq'::regclass);


--
-- Name: guard_inspections id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guard_inspections ALTER COLUMN id SET DEFAULT nextval('public.guard_inspections_id_seq'::regclass);


--
-- Name: help_articles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles ALTER COLUMN id SET DEFAULT nextval('public.help_articles_id_seq'::regclass);


--
-- Name: imports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imports ALTER COLUMN id SET DEFAULT nextval('public.imports_id_seq'::regclass);


--
-- Name: indents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.indents ALTER COLUMN id SET DEFAULT nextval('public.indents_id_seq'::regclass);


--
-- Name: invoices id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices ALTER COLUMN id SET DEFAULT nextval('public.invoices_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: lemon_squeezy_customers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_customers ALTER COLUMN id SET DEFAULT nextval('public.lemon_squeezy_customers_id_seq'::regclass);


--
-- Name: lemon_squeezy_license_key_instances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_key_instances ALTER COLUMN id SET DEFAULT nextval('public.lemon_squeezy_license_key_instances_id_seq'::regclass);


--
-- Name: lemon_squeezy_license_keys id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_keys ALTER COLUMN id SET DEFAULT nextval('public.lemon_squeezy_license_keys_id_seq'::regclass);


--
-- Name: lemon_squeezy_orders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_orders ALTER COLUMN id SET DEFAULT nextval('public.lemon_squeezy_orders_id_seq'::regclass);


--
-- Name: lemon_squeezy_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.lemon_squeezy_subscriptions_id_seq'::regclass);


--
-- Name: levels id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.levels ALTER COLUMN id SET DEFAULT nextval('public.levels_id_seq'::regclass);


--
-- Name: loader_performances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loader_performances ALTER COLUMN id SET DEFAULT nextval('public.loader_performances_id_seq'::regclass);


--
-- Name: loaders id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loaders ALTER COLUMN id SET DEFAULT nextval('public.loaders_id_seq'::regclass);


--
-- Name: mail_exceptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_exceptions ALTER COLUMN id SET DEFAULT nextval('public.mail_exceptions_id_seq'::regclass);


--
-- Name: mail_templates id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates ALTER COLUMN id SET DEFAULT nextval('public.mail_templates_id_seq'::regclass);


--
-- Name: media id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media ALTER COLUMN id SET DEFAULT nextval('public.media_id_seq'::regclass);


--
-- Name: memories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memories ALTER COLUMN id SET DEFAULT nextval('public.memories_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: model_flags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_flags ALTER COLUMN id SET DEFAULT nextval('public.model_flags_id_seq'::regclass);


--
-- Name: organization_domains id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_domains ALTER COLUMN id SET DEFAULT nextval('public.organization_domains_id_seq'::regclass);


--
-- Name: organization_invitations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_invitations ALTER COLUMN id SET DEFAULT nextval('public.organization_invitations_id_seq'::regclass);


--
-- Name: organizations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations ALTER COLUMN id SET DEFAULT nextval('public.organizations_id_seq'::regclass);


--
-- Name: pan_analytics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pan_analytics ALTER COLUMN id SET DEFAULT nextval('public.pan_analytics_id_seq'::regclass);


--
-- Name: payment_gateways id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateways ALTER COLUMN id SET DEFAULT nextval('public.payment_gateways_id_seq'::regclass);


--
-- Name: penalties id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalties ALTER COLUMN id SET DEFAULT nextval('public.penalties_id_seq'::regclass);


--
-- Name: penalty_predictions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_predictions ALTER COLUMN id SET DEFAULT nextval('public.penalty_predictions_id_seq'::regclass);


--
-- Name: penalty_types id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_types ALTER COLUMN id SET DEFAULT nextval('public.penalty_types_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: plan_features id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_features ALTER COLUMN id SET DEFAULT nextval('public.plan_features_id_seq'::regclass);


--
-- Name: plan_subscription_usage id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_subscription_usage ALTER COLUMN id SET DEFAULT nextval('public.plan_subscription_usage_id_seq'::regclass);


--
-- Name: plan_subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.plan_subscriptions_id_seq'::regclass);


--
-- Name: plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans ALTER COLUMN id SET DEFAULT nextval('public.plans_id_seq'::regclass);


--
-- Name: posts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts ALTER COLUMN id SET DEFAULT nextval('public.posts_id_seq'::regclass);


--
-- Name: power_plant_receipts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plant_receipts ALTER COLUMN id SET DEFAULT nextval('public.power_plant_receipts_id_seq'::regclass);


--
-- Name: power_plants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plants ALTER COLUMN id SET DEFAULT nextval('public.power_plants_id_seq'::regclass);


--
-- Name: rake_wagon_weighments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_wagon_weighments ALTER COLUMN id SET DEFAULT nextval('public.rake_wagon_weighments_id_seq'::regclass);


--
-- Name: rake_weighments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_weighments ALTER COLUMN id SET DEFAULT nextval('public.rake_weighments_id_seq'::regclass);


--
-- Name: rakes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes ALTER COLUMN id SET DEFAULT nextval('public.rakes_id_seq'::regclass);


--
-- Name: referrals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals ALTER COLUMN id SET DEFAULT nextval('public.referrals_id_seq'::regclass);


--
-- Name: refund_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests ALTER COLUMN id SET DEFAULT nextval('public.refund_requests_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: routes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.routes ALTER COLUMN id SET DEFAULT nextval('public.routes_id_seq'::regclass);


--
-- Name: rr_documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents ALTER COLUMN id SET DEFAULT nextval('public.rr_documents_id_seq'::regclass);


--
-- Name: rr_predictions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_predictions ALTER COLUMN id SET DEFAULT nextval('public.rr_predictions_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: shareables id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shareables ALTER COLUMN id SET DEFAULT nextval('public.shareables_id_seq'::regclass);


--
-- Name: siding_performance id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_performance ALTER COLUMN id SET DEFAULT nextval('public.siding_performance_id_seq'::regclass);


--
-- Name: siding_risk_scores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_risk_scores ALTER COLUMN id SET DEFAULT nextval('public.siding_risk_scores_id_seq'::regclass);


--
-- Name: siding_user id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_user ALTER COLUMN id SET DEFAULT nextval('public.siding_user_id_seq'::regclass);


--
-- Name: sidings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings ALTER COLUMN id SET DEFAULT nextval('public.sidings_id_seq'::regclass);


--
-- Name: stock_ledgers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_ledgers ALTER COLUMN id SET DEFAULT nextval('public.stock_ledgers_id_seq'::regclass);


--
-- Name: streak_activities id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_activities ALTER COLUMN id SET DEFAULT nextval('public.streak_activities_id_seq'::regclass);


--
-- Name: streak_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_histories ALTER COLUMN id SET DEFAULT nextval('public.streak_histories_id_seq'::regclass);


--
-- Name: streaks id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streaks ALTER COLUMN id SET DEFAULT nextval('public.streaks_id_seq'::regclass);


--
-- Name: sync_queue id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sync_queue ALTER COLUMN id SET DEFAULT nextval('public.sync_queue_id_seq'::regclass);


--
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- Name: terms_versions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.terms_versions ALTER COLUMN id SET DEFAULT nextval('public.terms_versions_id_seq'::regclass);


--
-- Name: txr id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr ALTER COLUMN id SET DEFAULT nextval('public.txr_id_seq'::regclass);


--
-- Name: user_siding id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_siding ALTER COLUMN id SET DEFAULT nextval('public.user_siding_id_seq'::regclass);


--
-- Name: user_terms_acceptances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_terms_acceptances ALTER COLUMN id SET DEFAULT nextval('public.user_terms_acceptances_id_seq'::regclass);


--
-- Name: user_voucher id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_voucher ALTER COLUMN id SET DEFAULT nextval('public.user_voucher_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: vehicle_arrivals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_arrivals ALTER COLUMN id SET DEFAULT nextval('public.vehicle_arrivals_id_seq'::regclass);


--
-- Name: vehicle_unload_steps id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_steps ALTER COLUMN id SET DEFAULT nextval('public.vehicle_unload_steps_id_seq'::regclass);


--
-- Name: vehicle_unload_weighments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_weighments ALTER COLUMN id SET DEFAULT nextval('public.vehicle_unload_weighments_id_seq'::regclass);


--
-- Name: vehicle_unloads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads ALTER COLUMN id SET DEFAULT nextval('public.vehicle_unloads_id_seq'::regclass);


--
-- Name: vehicles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicles ALTER COLUMN id SET DEFAULT nextval('public.vehicles_id_seq'::regclass);


--
-- Name: visibility_demos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visibility_demos ALTER COLUMN id SET DEFAULT nextval('public.visibility_demos_id_seq'::regclass);


--
-- Name: voucher_scopes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voucher_scopes ALTER COLUMN id SET DEFAULT nextval('public.voucher_scopes_id_seq'::regclass);


--
-- Name: vouchers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers ALTER COLUMN id SET DEFAULT nextval('public.vouchers_id_seq'::regclass);


--
-- Name: wagon_loading id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading ALTER COLUMN id SET DEFAULT nextval('public.wagon_loading_id_seq'::regclass);


--
-- Name: wagon_unfit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs ALTER COLUMN id SET DEFAULT nextval('public.wagon_unfit_logs_id_seq'::regclass);


--
-- Name: wagons id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagons ALTER COLUMN id SET DEFAULT nextval('public.wagons_id_seq'::regclass);


--
-- Name: webhook_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_logs ALTER COLUMN id SET DEFAULT nextval('public.webhook_logs_id_seq'::regclass);


--
-- Name: achievement_user achievement_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievement_user
    ADD CONSTRAINT achievement_user_pkey PRIMARY KEY (id);


--
-- Name: achievements achievements_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievements
    ADD CONSTRAINT achievements_pkey PRIMARY KEY (id);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: affiliate_commissions affiliate_commissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_commissions
    ADD CONSTRAINT affiliate_commissions_pkey PRIMARY KEY (id);


--
-- Name: affiliate_payouts affiliate_payouts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_payouts
    ADD CONSTRAINT affiliate_payouts_pkey PRIMARY KEY (id);


--
-- Name: affiliates affiliates_affiliate_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliates
    ADD CONSTRAINT affiliates_affiliate_code_unique UNIQUE (affiliate_code);


--
-- Name: affiliates affiliates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliates
    ADD CONSTRAINT affiliates_pkey PRIMARY KEY (id);


--
-- Name: agent_conversation_messages agent_conversation_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_conversation_messages
    ADD CONSTRAINT agent_conversation_messages_pkey PRIMARY KEY (id);


--
-- Name: agent_conversations agent_conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agent_conversations
    ADD CONSTRAINT agent_conversations_pkey PRIMARY KEY (id);


--
-- Name: alerts alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_pkey PRIMARY KEY (id);


--
-- Name: applied_penalties applied_penalties_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.applied_penalties
    ADD CONSTRAINT applied_penalties_pkey PRIMARY KEY (id);


--
-- Name: billing_metrics billing_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_metrics
    ADD CONSTRAINT billing_metrics_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categoryables categoryables_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoryables
    ADD CONSTRAINT categoryables_pkey PRIMARY KEY (category_id, categoryable_type, categoryable_id);


--
-- Name: changelog_entries changelog_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.changelog_entries
    ADD CONSTRAINT changelog_entries_pkey PRIMARY KEY (id);


--
-- Name: coal_stock coal_stock_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coal_stock
    ADD CONSTRAINT coal_stock_pkey PRIMARY KEY (id);


--
-- Name: coal_stock coal_stock_siding_id_as_of_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coal_stock
    ADD CONSTRAINT coal_stock_siding_id_as_of_date_unique UNIQUE (siding_id, as_of_date);


--
-- Name: contact_submissions contact_submissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_submissions
    ADD CONSTRAINT contact_submissions_pkey PRIMARY KEY (id);


--
-- Name: credit_packs credit_packs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_packs
    ADD CONSTRAINT credit_packs_pkey PRIMARY KEY (id);


--
-- Name: credit_packs credit_packs_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credit_packs
    ADD CONSTRAINT credit_packs_slug_unique UNIQUE (slug);


--
-- Name: credits credits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_pkey PRIMARY KEY (id);


--
-- Name: daily_vehicle_entries daily_vehicle_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries
    ADD CONSTRAINT daily_vehicle_entries_pkey PRIMARY KEY (id);


--
-- Name: daily_vehicle_entries daily_vehicle_entries_siding_id_entry_date_shift_vehicle_no_rea; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries
    ADD CONSTRAINT daily_vehicle_entries_siding_id_entry_date_shift_vehicle_no_rea UNIQUE (siding_id, entry_date, shift, vehicle_no, reached_at);


--
-- Name: embedding_demos embedding_demos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.embedding_demos
    ADD CONSTRAINT embedding_demos_pkey PRIMARY KEY (id);


--
-- Name: enterprise_inquiries enterprise_inquiries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.enterprise_inquiries
    ADD CONSTRAINT enterprise_inquiries_pkey PRIMARY KEY (id);


--
-- Name: experience_audits experience_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experience_audits
    ADD CONSTRAINT experience_audits_pkey PRIMARY KEY (id);


--
-- Name: experiences experiences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experiences
    ADD CONSTRAINT experiences_pkey PRIMARY KEY (id);


--
-- Name: exports exports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exports
    ADD CONSTRAINT exports_pkey PRIMARY KEY (id);


--
-- Name: failed_import_rows failed_import_rows_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_import_rows
    ADD CONSTRAINT failed_import_rows_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: failed_payment_attempts failed_payment_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_payment_attempts
    ADD CONSTRAINT failed_payment_attempts_pkey PRIMARY KEY (id);


--
-- Name: feature_segments feature_segments_feature_scope_active_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feature_segments
    ADD CONSTRAINT feature_segments_feature_scope_active_unique UNIQUE (feature, scope, active);


--
-- Name: feature_segments feature_segments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.feature_segments
    ADD CONSTRAINT feature_segments_pkey PRIMARY KEY (id);


--
-- Name: features features_name_scope_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.features
    ADD CONSTRAINT features_name_scope_unique UNIQUE (name, scope);


--
-- Name: features features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.features
    ADD CONSTRAINT features_pkey PRIMARY KEY (id);


--
-- Name: flags flags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.flags
    ADD CONSTRAINT flags_pkey PRIMARY KEY (id);


--
-- Name: freight_rate_master freight_rate_master_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.freight_rate_master
    ADD CONSTRAINT freight_rate_master_pkey PRIMARY KEY (id);


--
-- Name: gateway_products gateway_products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gateway_products
    ADD CONSTRAINT gateway_products_pkey PRIMARY KEY (id);


--
-- Name: guard_inspections guard_inspections_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guard_inspections
    ADD CONSTRAINT guard_inspections_pkey PRIMARY KEY (id);


--
-- Name: guard_inspections guard_inspections_rake_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guard_inspections
    ADD CONSTRAINT guard_inspections_rake_id_unique UNIQUE (rake_id);


--
-- Name: help_articles help_articles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles
    ADD CONSTRAINT help_articles_pkey PRIMARY KEY (id);


--
-- Name: help_articles help_articles_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles
    ADD CONSTRAINT help_articles_slug_unique UNIQUE (slug);


--
-- Name: imports imports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imports
    ADD CONSTRAINT imports_pkey PRIMARY KEY (id);


--
-- Name: indents indents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.indents
    ADD CONSTRAINT indents_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_number_unique UNIQUE (number);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: lemon_squeezy_customers lemon_squeezy_customers_billable_id_billable_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_customers
    ADD CONSTRAINT lemon_squeezy_customers_billable_id_billable_type_unique UNIQUE (billable_id, billable_type);


--
-- Name: lemon_squeezy_customers lemon_squeezy_customers_lemon_squeezy_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_customers
    ADD CONSTRAINT lemon_squeezy_customers_lemon_squeezy_id_unique UNIQUE (lemon_squeezy_id);


--
-- Name: lemon_squeezy_customers lemon_squeezy_customers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_customers
    ADD CONSTRAINT lemon_squeezy_customers_pkey PRIMARY KEY (id);


--
-- Name: lemon_squeezy_license_key_instances lemon_squeezy_license_key_instances_identifier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_key_instances
    ADD CONSTRAINT lemon_squeezy_license_key_instances_identifier_unique UNIQUE (identifier);


--
-- Name: lemon_squeezy_license_key_instances lemon_squeezy_license_key_instances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_key_instances
    ADD CONSTRAINT lemon_squeezy_license_key_instances_pkey PRIMARY KEY (id);


--
-- Name: lemon_squeezy_license_keys lemon_squeezy_license_keys_lemon_squeezy_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_keys
    ADD CONSTRAINT lemon_squeezy_license_keys_lemon_squeezy_id_unique UNIQUE (lemon_squeezy_id);


--
-- Name: lemon_squeezy_license_keys lemon_squeezy_license_keys_license_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_keys
    ADD CONSTRAINT lemon_squeezy_license_keys_license_key_unique UNIQUE (license_key);


--
-- Name: lemon_squeezy_license_keys lemon_squeezy_license_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_keys
    ADD CONSTRAINT lemon_squeezy_license_keys_pkey PRIMARY KEY (id);


--
-- Name: lemon_squeezy_orders lemon_squeezy_orders_identifier_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_orders
    ADD CONSTRAINT lemon_squeezy_orders_identifier_unique UNIQUE (identifier);


--
-- Name: lemon_squeezy_orders lemon_squeezy_orders_lemon_squeezy_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_orders
    ADD CONSTRAINT lemon_squeezy_orders_lemon_squeezy_id_unique UNIQUE (lemon_squeezy_id);


--
-- Name: lemon_squeezy_orders lemon_squeezy_orders_order_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_orders
    ADD CONSTRAINT lemon_squeezy_orders_order_number_unique UNIQUE (order_number);


--
-- Name: lemon_squeezy_orders lemon_squeezy_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_orders
    ADD CONSTRAINT lemon_squeezy_orders_pkey PRIMARY KEY (id);


--
-- Name: lemon_squeezy_subscriptions lemon_squeezy_subscriptions_lemon_squeezy_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_subscriptions
    ADD CONSTRAINT lemon_squeezy_subscriptions_lemon_squeezy_id_unique UNIQUE (lemon_squeezy_id);


--
-- Name: lemon_squeezy_subscriptions lemon_squeezy_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_subscriptions
    ADD CONSTRAINT lemon_squeezy_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: levels levels_level_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_level_unique UNIQUE (level);


--
-- Name: levels levels_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_pkey PRIMARY KEY (id);


--
-- Name: loader_performances loader_performances_loader_id_as_of_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loader_performances
    ADD CONSTRAINT loader_performances_loader_id_as_of_date_unique UNIQUE (loader_id, as_of_date);


--
-- Name: loader_performances loader_performances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loader_performances
    ADD CONSTRAINT loader_performances_pkey PRIMARY KEY (id);


--
-- Name: loaders loaders_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loaders
    ADD CONSTRAINT loaders_code_unique UNIQUE (code);


--
-- Name: loaders loaders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loaders
    ADD CONSTRAINT loaders_pkey PRIMARY KEY (id);


--
-- Name: mail_exceptions mail_exceptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_exceptions
    ADD CONSTRAINT mail_exceptions_pkey PRIMARY KEY (id);


--
-- Name: mail_templates mail_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_templates
    ADD CONSTRAINT mail_templates_pkey PRIMARY KEY (id);


--
-- Name: media media_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_pkey PRIMARY KEY (id);


--
-- Name: media media_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.media
    ADD CONSTRAINT media_uuid_unique UNIQUE (uuid);


--
-- Name: memories memories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memories
    ADD CONSTRAINT memories_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_flags model_flags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_flags
    ADD CONSTRAINT model_flags_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (organization_id, permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (organization_id, role_id, model_id, model_type);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: organization_domains organization_domains_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_domains
    ADD CONSTRAINT organization_domains_domain_unique UNIQUE (domain);


--
-- Name: organization_domains organization_domains_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_domains
    ADD CONSTRAINT organization_domains_pkey PRIMARY KEY (id);


--
-- Name: organization_invitations organization_invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_pkey PRIMARY KEY (id);


--
-- Name: organization_invitations organization_invitations_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_token_unique UNIQUE (token);


--
-- Name: organization_user organization_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_user
    ADD CONSTRAINT organization_user_pkey PRIMARY KEY (organization_id, user_id);


--
-- Name: organizations organizations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (id);


--
-- Name: organizations organizations_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_slug_unique UNIQUE (slug);


--
-- Name: pan_analytics pan_analytics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pan_analytics
    ADD CONSTRAINT pan_analytics_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payment_gateways payment_gateways_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payment_gateways
    ADD CONSTRAINT payment_gateways_pkey PRIMARY KEY (id);


--
-- Name: penalties penalties_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalties
    ADD CONSTRAINT penalties_pkey PRIMARY KEY (id);


--
-- Name: penalty_predictions penalty_predictions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_predictions
    ADD CONSTRAINT penalty_predictions_pkey PRIMARY KEY (id);


--
-- Name: penalty_types penalty_types_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_types
    ADD CONSTRAINT penalty_types_code_unique UNIQUE (code);


--
-- Name: penalty_types penalty_types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_types
    ADD CONSTRAINT penalty_types_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: plan_features plan_features_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_features
    ADD CONSTRAINT plan_features_pkey PRIMARY KEY (id);


--
-- Name: plan_features plan_features_plan_id_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_features
    ADD CONSTRAINT plan_features_plan_id_slug_unique UNIQUE (plan_id, slug);


--
-- Name: plan_subscription_usage plan_subscription_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_subscription_usage
    ADD CONSTRAINT plan_subscription_usage_pkey PRIMARY KEY (id);


--
-- Name: plan_subscriptions plan_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plan_subscriptions
    ADD CONSTRAINT plan_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_pkey PRIMARY KEY (id);


--
-- Name: plans plans_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_slug_unique UNIQUE (slug);


--
-- Name: posts posts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_pkey PRIMARY KEY (id);


--
-- Name: posts posts_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_slug_unique UNIQUE (slug);


--
-- Name: power_plant_receipts power_plant_receipts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plant_receipts
    ADD CONSTRAINT power_plant_receipts_pkey PRIMARY KEY (id);


--
-- Name: power_plants power_plants_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plants
    ADD CONSTRAINT power_plants_code_unique UNIQUE (code);


--
-- Name: power_plants power_plants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plants
    ADD CONSTRAINT power_plants_pkey PRIMARY KEY (id);


--
-- Name: rake_wagon_weighments rake_wagon_weighments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_wagon_weighments
    ADD CONSTRAINT rake_wagon_weighments_pkey PRIMARY KEY (id);


--
-- Name: rake_wagon_weighments rake_wagon_weighments_rake_weighment_id_wagon_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_wagon_weighments
    ADD CONSTRAINT rake_wagon_weighments_rake_weighment_id_wagon_id_unique UNIQUE (rake_weighment_id, wagon_id);


--
-- Name: rake_weighments rake_weighments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_weighments
    ADD CONSTRAINT rake_weighments_pkey PRIMARY KEY (id);


--
-- Name: rakes rakes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_pkey PRIMARY KEY (id);


--
-- Name: rakes rakes_rake_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_rake_number_unique UNIQUE (rake_number);


--
-- Name: referrals referrals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_pkey PRIMARY KEY (id);


--
-- Name: referrals referrals_referral_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_referral_code_unique UNIQUE (referral_code);


--
-- Name: refund_requests refund_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_organization_id_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_organization_id_name_guard_name_unique UNIQUE (organization_id, name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: routes routes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_pkey PRIMARY KEY (id);


--
-- Name: rr_documents rr_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents
    ADD CONSTRAINT rr_documents_pkey PRIMARY KEY (id);


--
-- Name: rr_documents rr_documents_rr_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents
    ADD CONSTRAINT rr_documents_rr_number_unique UNIQUE (rr_number);


--
-- Name: rr_predictions rr_predictions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_predictions
    ADD CONSTRAINT rr_predictions_pkey PRIMARY KEY (id);


--
-- Name: rr_predictions rr_predictions_rake_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_predictions
    ADD CONSTRAINT rr_predictions_rake_id_unique UNIQUE (rake_id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_group_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_group_name_unique UNIQUE ("group", name);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: shareables shareables_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shareables
    ADD CONSTRAINT shareables_pkey PRIMARY KEY (id);


--
-- Name: shareables shareables_unique_share; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shareables
    ADD CONSTRAINT shareables_unique_share UNIQUE (shareable_type, shareable_id, target_type, target_id);


--
-- Name: siding_performance siding_performance_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_performance
    ADD CONSTRAINT siding_performance_pkey PRIMARY KEY (id);


--
-- Name: siding_performance siding_performance_siding_id_as_of_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_performance
    ADD CONSTRAINT siding_performance_siding_id_as_of_date_unique UNIQUE (siding_id, as_of_date);


--
-- Name: siding_risk_scores siding_risk_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_risk_scores
    ADD CONSTRAINT siding_risk_scores_pkey PRIMARY KEY (id);


--
-- Name: siding_risk_scores siding_risk_scores_siding_id_calculated_at_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_risk_scores
    ADD CONSTRAINT siding_risk_scores_siding_id_calculated_at_unique UNIQUE (siding_id, calculated_at);


--
-- Name: siding_user siding_user_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_user
    ADD CONSTRAINT siding_user_pkey PRIMARY KEY (id);


--
-- Name: siding_user siding_user_user_id_siding_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_user
    ADD CONSTRAINT siding_user_user_id_siding_id_unique UNIQUE (user_id, siding_id);


--
-- Name: sidings sidings_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings
    ADD CONSTRAINT sidings_code_unique UNIQUE (code);


--
-- Name: sidings sidings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings
    ADD CONSTRAINT sidings_pkey PRIMARY KEY (id);


--
-- Name: stock_ledgers stock_ledgers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_ledgers
    ADD CONSTRAINT stock_ledgers_pkey PRIMARY KEY (id);


--
-- Name: streak_activities streak_activities_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_activities
    ADD CONSTRAINT streak_activities_name_unique UNIQUE (name);


--
-- Name: streak_activities streak_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_activities
    ADD CONSTRAINT streak_activities_pkey PRIMARY KEY (id);


--
-- Name: streak_histories streak_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_histories
    ADD CONSTRAINT streak_histories_pkey PRIMARY KEY (id);


--
-- Name: streaks streaks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streaks
    ADD CONSTRAINT streaks_pkey PRIMARY KEY (id);


--
-- Name: sync_queue sync_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sync_queue
    ADD CONSTRAINT sync_queue_pkey PRIMARY KEY (id);


--
-- Name: taggables taggables_tag_id_taggable_id_taggable_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_taggable_id_taggable_type_unique UNIQUE (tag_id, taggable_id, taggable_type);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- Name: terms_versions terms_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.terms_versions
    ADD CONSTRAINT terms_versions_pkey PRIMARY KEY (id);


--
-- Name: terms_versions terms_versions_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.terms_versions
    ADD CONSTRAINT terms_versions_slug_unique UNIQUE (slug);


--
-- Name: txr txr_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr
    ADD CONSTRAINT txr_pkey PRIMARY KEY (id);


--
-- Name: txr txr_rake_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr
    ADD CONSTRAINT txr_rake_id_unique UNIQUE (rake_id);


--
-- Name: user_siding user_siding_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_siding
    ADD CONSTRAINT user_siding_pkey PRIMARY KEY (id);


--
-- Name: user_siding user_siding_user_id_siding_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_siding
    ADD CONSTRAINT user_siding_user_id_siding_id_unique UNIQUE (user_id, siding_id);


--
-- Name: user_terms_acceptances user_terms_acceptances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_terms_acceptances
    ADD CONSTRAINT user_terms_acceptances_pkey PRIMARY KEY (id);


--
-- Name: user_terms_acceptances user_terms_acceptances_user_id_terms_version_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_terms_acceptances
    ADD CONSTRAINT user_terms_acceptances_user_id_terms_version_id_unique UNIQUE (user_id, terms_version_id);


--
-- Name: user_voucher user_voucher_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_voucher
    ADD CONSTRAINT user_voucher_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: vehicle_arrivals vehicle_arrivals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_arrivals
    ADD CONSTRAINT vehicle_arrivals_pkey PRIMARY KEY (id);


--
-- Name: vehicle_unload_steps vehicle_unload_steps_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_steps
    ADD CONSTRAINT vehicle_unload_steps_pkey PRIMARY KEY (id);


--
-- Name: vehicle_unload_steps vehicle_unload_steps_vehicle_unload_id_step_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_steps
    ADD CONSTRAINT vehicle_unload_steps_vehicle_unload_id_step_number_unique UNIQUE (vehicle_unload_id, step_number);


--
-- Name: vehicle_unload_weighments vehicle_unload_weighments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_weighments
    ADD CONSTRAINT vehicle_unload_weighments_pkey PRIMARY KEY (id);


--
-- Name: vehicle_unloads vehicle_unloads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_pkey PRIMARY KEY (id);


--
-- Name: vehicles vehicles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_pkey PRIMARY KEY (id);


--
-- Name: vehicles vehicles_rfid_tag_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_rfid_tag_unique UNIQUE (rfid_tag);


--
-- Name: vehicles vehicles_vehicle_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicles
    ADD CONSTRAINT vehicles_vehicle_number_unique UNIQUE (vehicle_number);


--
-- Name: visibility_demos visibility_demos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visibility_demos
    ADD CONSTRAINT visibility_demos_pkey PRIMARY KEY (id);


--
-- Name: voucher_scopes voucher_scopes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.voucher_scopes
    ADD CONSTRAINT voucher_scopes_pkey PRIMARY KEY (id);


--
-- Name: vouchers vouchers_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_code_unique UNIQUE (code);


--
-- Name: vouchers vouchers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_pkey PRIMARY KEY (id);


--
-- Name: wagon_loading wagon_loading_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading
    ADD CONSTRAINT wagon_loading_pkey PRIMARY KEY (id);


--
-- Name: wagon_loading wagon_loading_rake_id_wagon_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading
    ADD CONSTRAINT wagon_loading_rake_id_wagon_id_unique UNIQUE (rake_id, wagon_id);


--
-- Name: wagon_unfit_logs wagon_unfit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs
    ADD CONSTRAINT wagon_unfit_logs_pkey PRIMARY KEY (id);


--
-- Name: wagon_unfit_logs wagon_unfit_logs_txr_id_wagon_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs
    ADD CONSTRAINT wagon_unfit_logs_txr_id_wagon_id_unique UNIQUE (txr_id, wagon_id);


--
-- Name: wagons wagons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagons
    ADD CONSTRAINT wagons_pkey PRIMARY KEY (id);


--
-- Name: wagons wagons_wagon_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagons
    ADD CONSTRAINT wagons_wagon_number_unique UNIQUE (wagon_number);


--
-- Name: webhook_logs webhook_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_logs
    ADD CONSTRAINT webhook_logs_pkey PRIMARY KEY (id);


--
-- Name: achievement_user_progress_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX achievement_user_progress_index ON public.achievement_user USING btree (progress);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: affiliate_commissions_affiliate_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX affiliate_commissions_affiliate_id_status_index ON public.affiliate_commissions USING btree (affiliate_id, status);


--
-- Name: affiliate_payouts_affiliate_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX affiliate_payouts_affiliate_id_status_index ON public.affiliate_payouts USING btree (affiliate_id, status);


--
-- Name: affiliates_affiliate_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX affiliates_affiliate_code_index ON public.affiliates USING btree (affiliate_code);


--
-- Name: affiliates_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX affiliates_status_index ON public.affiliates USING btree (status);


--
-- Name: agent_conversation_messages_conversation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_conversation_messages_conversation_id_index ON public.agent_conversation_messages USING btree (conversation_id);


--
-- Name: agent_conversation_messages_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_conversation_messages_user_id_index ON public.agent_conversation_messages USING btree (user_id);


--
-- Name: agent_conversations_user_id_updated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX agent_conversations_user_id_updated_at_index ON public.agent_conversations USING btree (user_id, updated_at);


--
-- Name: alerts_rake_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alerts_rake_id_status_index ON public.alerts USING btree (rake_id, status);


--
-- Name: alerts_siding_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alerts_siding_id_status_index ON public.alerts USING btree (siding_id, status);


--
-- Name: applied_penalties_rake_id_wagon_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX applied_penalties_rake_id_wagon_id_index ON public.applied_penalties USING btree (rake_id, wagon_id);


--
-- Name: categories__lft__rgt_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories__lft__rgt_parent_id_index ON public.categories USING btree (_lft, _rgt, parent_id);


--
-- Name: categories_organization_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_organization_id_index ON public.categories USING btree (organization_id);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: changelog_entries_is_published_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX changelog_entries_is_published_index ON public.changelog_entries USING btree (is_published);


--
-- Name: changelog_entries_organization_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX changelog_entries_organization_id_index ON public.changelog_entries USING btree (organization_id);


--
-- Name: changelog_entries_released_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX changelog_entries_released_at_index ON public.changelog_entries USING btree (released_at);


--
-- Name: changelog_entries_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX changelog_entries_type_index ON public.changelog_entries USING btree (type);


--
-- Name: coal_stock_siding_id_as_of_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX coal_stock_siding_id_as_of_date_index ON public.coal_stock USING btree (siding_id, as_of_date);


--
-- Name: contact_submissions_organization_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX contact_submissions_organization_id_index ON public.contact_submissions USING btree (organization_id);


--
-- Name: conversation_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX conversation_index ON public.agent_conversation_messages USING btree (conversation_id, user_id, updated_at);


--
-- Name: credits_creditable_type_creditable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX credits_creditable_type_creditable_id_index ON public.credits USING btree (creditable_type, creditable_id);


--
-- Name: daily_vehicle_entries_entry_date_shift_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX daily_vehicle_entries_entry_date_shift_index ON public.daily_vehicle_entries USING btree (entry_date, shift);


--
-- Name: daily_vehicle_entries_siding_id_entry_date_vehicle_no_e_challan; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX daily_vehicle_entries_siding_id_entry_date_vehicle_no_e_challan ON public.daily_vehicle_entries USING btree (siding_id, entry_date, vehicle_no, e_challan_no, challan_mode, status);


--
-- Name: experience_audits_points_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX experience_audits_points_index ON public.experience_audits USING btree (points);


--
-- Name: experiences_experience_points_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX experiences_experience_points_index ON public.experiences USING btree (experience_points);


--
-- Name: failed_payment_attempts_organization_id_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_payment_attempts_organization_id_failed_at_index ON public.failed_payment_attempts USING btree (organization_id, failed_at);


--
-- Name: flags_flaggable_type_flaggable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flags_flaggable_type_flaggable_id_index ON public.flags USING btree (flaggable_type, flaggable_id);


--
-- Name: flags_name_flaggable_id_flaggable_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX flags_name_flaggable_id_flaggable_type_index ON public.flags USING btree (name, flaggable_id, flaggable_type);


--
-- Name: freight_rate_master_class_code_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX freight_rate_master_class_code_is_active_index ON public.freight_rate_master USING btree (class_code, is_active);


--
-- Name: freight_rate_master_commodity_code_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX freight_rate_master_commodity_code_is_active_index ON public.freight_rate_master USING btree (commodity_code, is_active);


--
-- Name: help_articles_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX help_articles_category_index ON public.help_articles USING btree (category);


--
-- Name: help_articles_is_featured_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX help_articles_is_featured_index ON public.help_articles USING btree (is_featured);


--
-- Name: help_articles_is_published_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX help_articles_is_published_index ON public.help_articles USING btree (is_published);


--
-- Name: help_articles_organization_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX help_articles_organization_id_index ON public.help_articles USING btree (organization_id);


--
-- Name: help_articles_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX help_articles_slug_index ON public.help_articles USING btree (slug);


--
-- Name: indents_siding_id_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX indents_siding_id_state_index ON public.indents USING btree (siding_id, state);


--
-- Name: invoices_billable_type_billable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_billable_type_billable_id_index ON public.invoices USING btree (billable_type, billable_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: lemon_squeezy_license_keys_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_license_keys_order_id_index ON public.lemon_squeezy_license_keys USING btree (order_id);


--
-- Name: lemon_squeezy_license_keys_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_license_keys_product_id_index ON public.lemon_squeezy_license_keys USING btree (product_id);


--
-- Name: lemon_squeezy_orders_billable_type_billable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_orders_billable_type_billable_id_index ON public.lemon_squeezy_orders USING btree (billable_type, billable_id);


--
-- Name: lemon_squeezy_orders_product_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_orders_product_id_index ON public.lemon_squeezy_orders USING btree (product_id);


--
-- Name: lemon_squeezy_orders_variant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_orders_variant_id_index ON public.lemon_squeezy_orders USING btree (variant_id);


--
-- Name: lemon_squeezy_subscriptions_billable_type_billable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lemon_squeezy_subscriptions_billable_type_billable_id_index ON public.lemon_squeezy_subscriptions USING btree (billable_type, billable_id);


--
-- Name: levels_next_level_experience_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX levels_next_level_experience_index ON public.levels USING btree (next_level_experience);


--
-- Name: loaders_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loaders_code_index ON public.loaders USING btree (code);


--
-- Name: loaders_siding_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX loaders_siding_id_is_active_index ON public.loaders USING btree (siding_id, is_active);


--
-- Name: mail_templates_event_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX mail_templates_event_index ON public.mail_templates USING btree (event);


--
-- Name: media_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_model_type_model_id_index ON public.media USING btree (model_type, model_id);


--
-- Name: media_order_column_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX media_order_column_index ON public.media USING btree (order_column);


--
-- Name: memories_embedding_vectorindex; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX memories_embedding_vectorindex ON public.memories USING hnsw (embedding public.vector_cosine_ops);


--
-- Name: memories_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX memories_user_id_created_at_index ON public.memories USING btree (user_id, created_at);


--
-- Name: memories_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX memories_user_id_index ON public.memories USING btree (user_id);


--
-- Name: model_flags_flaggable_type_flaggable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_flags_flaggable_type_flaggable_id_index ON public.model_flags USING btree (flaggable_type, flaggable_id);


--
-- Name: model_flags_name_flaggable_id_flaggable_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_flags_name_flaggable_id_flaggable_type_index ON public.model_flags USING btree (name, flaggable_id, flaggable_type);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_permissions_team_foreign_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_team_foreign_key_index ON public.model_has_permissions USING btree (organization_id);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: model_has_roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_team_foreign_key_index ON public.model_has_roles USING btree (organization_id);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: organization_invitations_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX organization_invitations_email_index ON public.organization_invitations USING btree (email);


--
-- Name: organization_invitations_organization_id_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX organization_invitations_organization_id_email_index ON public.organization_invitations USING btree (organization_id, email);


--
-- Name: organization_invitations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX organization_invitations_status_index ON public.organization_invitations USING btree (status);


--
-- Name: organization_user_unique_default; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX organization_user_unique_default ON public.organization_user USING btree (user_id) WHERE (is_default = true);


--
-- Name: organization_user_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX organization_user_user_id_index ON public.organization_user USING btree (user_id);


--
-- Name: organizations_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX organizations_name_index ON public.organizations USING btree (name);


--
-- Name: penalties_penalty_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penalties_penalty_type_index ON public.penalties USING btree (penalty_type);


--
-- Name: penalties_rake_id_penalty_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penalties_rake_id_penalty_status_index ON public.penalties USING btree (rake_id, penalty_status);


--
-- Name: penalties_responsible_party_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penalties_responsible_party_index ON public.penalties USING btree (responsible_party);


--
-- Name: penalty_predictions_prediction_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penalty_predictions_prediction_date_index ON public.penalty_predictions USING btree (prediction_date);


--
-- Name: penalty_predictions_siding_id_prediction_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX penalty_predictions_siding_id_prediction_date_index ON public.penalty_predictions USING btree (siding_id, prediction_date);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: plan_subscriptions_subscriber_type_subscriber_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX plan_subscriptions_subscriber_type_subscriber_id_index ON public.plan_subscriptions USING btree (subscriber_type, subscriber_id);


--
-- Name: posts_is_published_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX posts_is_published_index ON public.posts USING btree (is_published);


--
-- Name: posts_organization_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX posts_organization_id_index ON public.posts USING btree (organization_id);


--
-- Name: posts_published_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX posts_published_at_index ON public.posts USING btree (published_at);


--
-- Name: posts_slug_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX posts_slug_index ON public.posts USING btree (slug);


--
-- Name: power_plant_receipts_rake_id_power_plant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX power_plant_receipts_rake_id_power_plant_id_index ON public.power_plant_receipts USING btree (rake_id, power_plant_id);


--
-- Name: power_plants_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX power_plants_is_active_index ON public.power_plants USING btree (is_active);


--
-- Name: rake_weighments_rake_id_attempt_no_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rake_weighments_rake_id_attempt_no_index ON public.rake_weighments USING btree (rake_id, attempt_no);


--
-- Name: rakes_indent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rakes_indent_id_index ON public.rakes USING btree (indent_id);


--
-- Name: rakes_siding_id_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rakes_siding_id_state_index ON public.rakes USING btree (siding_id, state);


--
-- Name: referrals_referral_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_referral_code_index ON public.referrals USING btree (referral_code);


--
-- Name: referrals_referrer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_referrer_id_index ON public.referrals USING btree (referrer_id);


--
-- Name: referrals_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX referrals_user_id_index ON public.referrals USING btree (user_id);


--
-- Name: roles_team_foreign_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_team_foreign_key_index ON public.roles USING btree (organization_id);


--
-- Name: routes_siding_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX routes_siding_id_is_active_index ON public.routes USING btree (siding_id, is_active);


--
-- Name: rr_documents_rake_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rr_documents_rake_id_index ON public.rr_documents USING btree (rake_id);


--
-- Name: rr_documents_rr_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rr_documents_rr_number_index ON public.rr_documents USING btree (rr_number);


--
-- Name: rr_predictions_predicted_rr_date_prediction_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rr_predictions_predicted_rr_date_prediction_status_index ON public.rr_predictions USING btree (predicted_rr_date, prediction_status);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: shareables_shareable_type_shareable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX shareables_shareable_type_shareable_id_index ON public.shareables USING btree (shareable_type, shareable_id);


--
-- Name: shareables_target_type_target_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX shareables_target_type_target_id_index ON public.shareables USING btree (target_type, target_id);


--
-- Name: siding_risk_scores_siding_id_calculated_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX siding_risk_scores_siding_id_calculated_at_index ON public.siding_risk_scores USING btree (siding_id, calculated_at);


--
-- Name: siding_user_siding_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX siding_user_siding_id_user_id_index ON public.siding_user USING btree (siding_id, user_id);


--
-- Name: sidings_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sidings_code_index ON public.sidings USING btree (code);


--
-- Name: sidings_organization_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sidings_organization_id_is_active_index ON public.sidings USING btree (organization_id, is_active);


--
-- Name: stock_ledgers_rake_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_ledgers_rake_id_index ON public.stock_ledgers USING btree (rake_id);


--
-- Name: stock_ledgers_siding_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_ledgers_siding_id_created_at_index ON public.stock_ledgers USING btree (siding_id, created_at);


--
-- Name: stock_ledgers_transaction_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_ledgers_transaction_type_index ON public.stock_ledgers USING btree (transaction_type);


--
-- Name: stock_ledgers_vehicle_arrival_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX stock_ledgers_vehicle_arrival_id_index ON public.stock_ledgers USING btree (vehicle_arrival_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: sync_queue_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sync_queue_status_created_at_index ON public.sync_queue USING btree (status, created_at);


--
-- Name: sync_queue_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sync_queue_user_id_index ON public.sync_queue USING btree (user_id);


--
-- Name: taggables_taggable_type_taggable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX taggables_taggable_type_taggable_id_index ON public.taggables USING btree (taggable_type, taggable_id);


--
-- Name: user_siding_siding_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_siding_siding_id_index ON public.user_siding USING btree (siding_id);


--
-- Name: user_siding_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_siding_user_id_index ON public.user_siding USING btree (user_id);


--
-- Name: vehicle_arrivals_arrived_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicle_arrivals_arrived_at_index ON public.vehicle_arrivals USING btree (arrived_at);


--
-- Name: vehicle_arrivals_siding_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicle_arrivals_siding_id_status_index ON public.vehicle_arrivals USING btree (siding_id, status);


--
-- Name: vehicle_arrivals_vehicle_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicle_arrivals_vehicle_id_index ON public.vehicle_arrivals USING btree (vehicle_id);


--
-- Name: vehicle_unloads_siding_id_state_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicle_unloads_siding_id_state_index ON public.vehicle_unloads USING btree (siding_id, state);


--
-- Name: vehicle_unloads_vehicle_id_arrival_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicle_unloads_vehicle_id_arrival_time_index ON public.vehicle_unloads USING btree (vehicle_id, arrival_time);


--
-- Name: vehicles_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicles_is_active_index ON public.vehicles USING btree (is_active);


--
-- Name: vehicles_vehicle_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vehicles_vehicle_number_index ON public.vehicles USING btree (vehicle_number);


--
-- Name: visibility_demos_organization_id_visibility_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX visibility_demos_organization_id_visibility_index ON public.visibility_demos USING btree (organization_id, visibility);


--
-- Name: vouchers_model_type_model_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX vouchers_model_type_model_id_index ON public.vouchers USING btree (model_type, model_id);


--
-- Name: wagons_rake_id_wagon_sequence_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX wagons_rake_id_wagon_sequence_index ON public.wagons USING btree (rake_id, wagon_sequence);


--
-- Name: achievement_user achievement_user_achievement_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievement_user
    ADD CONSTRAINT achievement_user_achievement_id_foreign FOREIGN KEY (achievement_id) REFERENCES public.achievements(id);


--
-- Name: achievement_user achievement_user_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.achievement_user
    ADD CONSTRAINT achievement_user_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: affiliate_commissions affiliate_commissions_affiliate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_commissions
    ADD CONSTRAINT affiliate_commissions_affiliate_id_foreign FOREIGN KEY (affiliate_id) REFERENCES public.affiliates(id) ON DELETE CASCADE;


--
-- Name: affiliate_commissions affiliate_commissions_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_commissions
    ADD CONSTRAINT affiliate_commissions_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: affiliate_commissions affiliate_commissions_referred_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_commissions
    ADD CONSTRAINT affiliate_commissions_referred_organization_id_foreign FOREIGN KEY (referred_organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: affiliate_payouts affiliate_payouts_affiliate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_payouts
    ADD CONSTRAINT affiliate_payouts_affiliate_id_foreign FOREIGN KEY (affiliate_id) REFERENCES public.affiliates(id) ON DELETE CASCADE;


--
-- Name: affiliate_payouts affiliate_payouts_processed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliate_payouts
    ADD CONSTRAINT affiliate_payouts_processed_by_foreign FOREIGN KEY (processed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: affiliates affiliates_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.affiliates
    ADD CONSTRAINT affiliates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: alerts alerts_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: alerts alerts_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: alerts alerts_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: alerts alerts_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: applied_penalties applied_penalties_penalty_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.applied_penalties
    ADD CONSTRAINT applied_penalties_penalty_type_id_foreign FOREIGN KEY (penalty_type_id) REFERENCES public.penalty_types(id) ON DELETE CASCADE;


--
-- Name: applied_penalties applied_penalties_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.applied_penalties
    ADD CONSTRAINT applied_penalties_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: applied_penalties applied_penalties_wagon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.applied_penalties
    ADD CONSTRAINT applied_penalties_wagon_id_foreign FOREIGN KEY (wagon_id) REFERENCES public.wagons(id) ON DELETE CASCADE;


--
-- Name: billing_metrics billing_metrics_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_metrics
    ADD CONSTRAINT billing_metrics_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: categories categories_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: categoryables categoryables_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categoryables
    ADD CONSTRAINT categoryables_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: changelog_entries changelog_entries_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.changelog_entries
    ADD CONSTRAINT changelog_entries_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: changelog_entries changelog_entries_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.changelog_entries
    ADD CONSTRAINT changelog_entries_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: changelog_entries changelog_entries_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.changelog_entries
    ADD CONSTRAINT changelog_entries_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: coal_stock coal_stock_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.coal_stock
    ADD CONSTRAINT coal_stock_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: contact_submissions contact_submissions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_submissions
    ADD CONSTRAINT contact_submissions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: contact_submissions contact_submissions_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_submissions
    ADD CONSTRAINT contact_submissions_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: contact_submissions contact_submissions_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_submissions
    ADD CONSTRAINT contact_submissions_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: credits credits_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.credits
    ADD CONSTRAINT credits_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: daily_vehicle_entries daily_vehicle_entries_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries
    ADD CONSTRAINT daily_vehicle_entries_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: daily_vehicle_entries daily_vehicle_entries_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries
    ADD CONSTRAINT daily_vehicle_entries_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: daily_vehicle_entries daily_vehicle_entries_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.daily_vehicle_entries
    ADD CONSTRAINT daily_vehicle_entries_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: experience_audits experience_audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experience_audits
    ADD CONSTRAINT experience_audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: experiences experiences_level_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experiences
    ADD CONSTRAINT experiences_level_id_foreign FOREIGN KEY (level_id) REFERENCES public.levels(id);


--
-- Name: experiences experiences_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.experiences
    ADD CONSTRAINT experiences_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: exports exports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.exports
    ADD CONSTRAINT exports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: failed_import_rows failed_import_rows_import_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_import_rows
    ADD CONSTRAINT failed_import_rows_import_id_foreign FOREIGN KEY (import_id) REFERENCES public.imports(id) ON DELETE CASCADE;


--
-- Name: failed_payment_attempts failed_payment_attempts_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_payment_attempts
    ADD CONSTRAINT failed_payment_attempts_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: gateway_products gateway_products_payment_gateway_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gateway_products
    ADD CONSTRAINT gateway_products_payment_gateway_id_foreign FOREIGN KEY (payment_gateway_id) REFERENCES public.payment_gateways(id) ON DELETE CASCADE;


--
-- Name: gateway_products gateway_products_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.gateway_products
    ADD CONSTRAINT gateway_products_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES public.plans(id) ON DELETE CASCADE;


--
-- Name: guard_inspections guard_inspections_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guard_inspections
    ADD CONSTRAINT guard_inspections_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: guard_inspections guard_inspections_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.guard_inspections
    ADD CONSTRAINT guard_inspections_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: help_articles help_articles_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles
    ADD CONSTRAINT help_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: help_articles help_articles_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles
    ADD CONSTRAINT help_articles_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: help_articles help_articles_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.help_articles
    ADD CONSTRAINT help_articles_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: imports imports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.imports
    ADD CONSTRAINT imports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: indents indents_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.indents
    ADD CONSTRAINT indents_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: indents indents_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.indents
    ADD CONSTRAINT indents_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: indents indents_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.indents
    ADD CONSTRAINT indents_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: invoices invoices_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_payment_gateway_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_payment_gateway_id_foreign FOREIGN KEY (payment_gateway_id) REFERENCES public.payment_gateways(id) ON DELETE SET NULL;


--
-- Name: lemon_squeezy_license_keys lemon_squeezy_license_keys_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lemon_squeezy_license_keys
    ADD CONSTRAINT lemon_squeezy_license_keys_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.lemon_squeezy_orders(lemon_squeezy_id);


--
-- Name: loader_performances loader_performances_loader_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loader_performances
    ADD CONSTRAINT loader_performances_loader_id_foreign FOREIGN KEY (loader_id) REFERENCES public.loaders(id) ON DELETE CASCADE;


--
-- Name: loaders loaders_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.loaders
    ADD CONSTRAINT loaders_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: mail_exceptions mail_exceptions_mail_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mail_exceptions
    ADD CONSTRAINT mail_exceptions_mail_template_id_foreign FOREIGN KEY (mail_template_id) REFERENCES public.mail_templates(id) ON DELETE CASCADE;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: organization_domains organization_domains_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_domains
    ADD CONSTRAINT organization_domains_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: organization_invitations organization_invitations_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: organization_invitations organization_invitations_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_invitations
    ADD CONSTRAINT organization_invitations_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: organization_user organization_user_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_user
    ADD CONSTRAINT organization_user_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: organization_user organization_user_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_user
    ADD CONSTRAINT organization_user_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: organization_user organization_user_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organization_user
    ADD CONSTRAINT organization_user_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: organizations organizations_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: organizations organizations_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: organizations organizations_owner_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: organizations organizations_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organizations
    ADD CONSTRAINT organizations_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penalties penalties_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalties
    ADD CONSTRAINT penalties_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: penalty_predictions penalty_predictions_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.penalty_predictions
    ADD CONSTRAINT penalty_predictions_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: posts posts_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: posts posts_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: posts posts_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: posts posts_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.posts
    ADD CONSTRAINT posts_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: power_plant_receipts power_plant_receipts_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plant_receipts
    ADD CONSTRAINT power_plant_receipts_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: power_plant_receipts power_plant_receipts_power_plant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plant_receipts
    ADD CONSTRAINT power_plant_receipts_power_plant_id_foreign FOREIGN KEY (power_plant_id) REFERENCES public.power_plants(id) ON DELETE CASCADE;


--
-- Name: power_plant_receipts power_plant_receipts_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.power_plant_receipts
    ADD CONSTRAINT power_plant_receipts_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: rake_wagon_weighments rake_wagon_weighments_rake_weighment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_wagon_weighments
    ADD CONSTRAINT rake_wagon_weighments_rake_weighment_id_foreign FOREIGN KEY (rake_weighment_id) REFERENCES public.rake_weighments(id) ON DELETE CASCADE;


--
-- Name: rake_wagon_weighments rake_wagon_weighments_wagon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_wagon_weighments
    ADD CONSTRAINT rake_wagon_weighments_wagon_id_foreign FOREIGN KEY (wagon_id) REFERENCES public.wagons(id) ON DELETE CASCADE;


--
-- Name: rake_weighments rake_weighments_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_weighments
    ADD CONSTRAINT rake_weighments_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rake_weighments rake_weighments_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rake_weighments
    ADD CONSTRAINT rake_weighments_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: rakes rakes_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rakes rakes_deleted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_deleted_by_foreign FOREIGN KEY (deleted_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rakes rakes_indent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_indent_id_foreign FOREIGN KEY (indent_id) REFERENCES public.indents(id) ON DELETE CASCADE;


--
-- Name: rakes rakes_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: rakes rakes_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rakes
    ADD CONSTRAINT rakes_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: referrals referrals_referrer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_referrer_id_foreign FOREIGN KEY (referrer_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: referrals referrals_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.referrals
    ADD CONSTRAINT referrals_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: refund_requests refund_requests_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: refund_requests refund_requests_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.refund_requests
    ADD CONSTRAINT refund_requests_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: routes routes_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: rr_documents rr_documents_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents
    ADD CONSTRAINT rr_documents_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rr_documents rr_documents_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents
    ADD CONSTRAINT rr_documents_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: rr_documents rr_documents_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_documents
    ADD CONSTRAINT rr_documents_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rr_predictions rr_predictions_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rr_predictions
    ADD CONSTRAINT rr_predictions_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: shareables shareables_shared_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shareables
    ADD CONSTRAINT shareables_shared_by_foreign FOREIGN KEY (shared_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: siding_performance siding_performance_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_performance
    ADD CONSTRAINT siding_performance_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: siding_risk_scores siding_risk_scores_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_risk_scores
    ADD CONSTRAINT siding_risk_scores_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: siding_user siding_user_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_user
    ADD CONSTRAINT siding_user_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: siding_user siding_user_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.siding_user
    ADD CONSTRAINT siding_user_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: sidings sidings_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings
    ADD CONSTRAINT sidings_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: sidings sidings_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings
    ADD CONSTRAINT sidings_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE CASCADE;


--
-- Name: sidings sidings_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sidings
    ADD CONSTRAINT sidings_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_ledgers stock_ledgers_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_ledgers
    ADD CONSTRAINT stock_ledgers_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id);


--
-- Name: stock_ledgers stock_ledgers_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_ledgers
    ADD CONSTRAINT stock_ledgers_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id);


--
-- Name: stock_ledgers stock_ledgers_vehicle_arrival_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.stock_ledgers
    ADD CONSTRAINT stock_ledgers_vehicle_arrival_id_foreign FOREIGN KEY (vehicle_arrival_id) REFERENCES public.vehicle_arrivals(id);


--
-- Name: streak_histories streak_histories_activity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_histories
    ADD CONSTRAINT streak_histories_activity_id_foreign FOREIGN KEY (activity_id) REFERENCES public.streak_activities(id);


--
-- Name: streak_histories streak_histories_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streak_histories
    ADD CONSTRAINT streak_histories_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: streaks streaks_activity_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streaks
    ADD CONSTRAINT streaks_activity_id_foreign FOREIGN KEY (activity_id) REFERENCES public.streak_activities(id) ON DELETE CASCADE;


--
-- Name: streaks streaks_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.streaks
    ADD CONSTRAINT streaks_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: sync_queue sync_queue_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sync_queue
    ADD CONSTRAINT sync_queue_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: taggables taggables_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.taggables
    ADD CONSTRAINT taggables_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- Name: txr txr_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr
    ADD CONSTRAINT txr_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: txr txr_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr
    ADD CONSTRAINT txr_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: txr txr_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.txr
    ADD CONSTRAINT txr_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: user_siding user_siding_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_siding
    ADD CONSTRAINT user_siding_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: user_siding user_siding_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_siding
    ADD CONSTRAINT user_siding_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_terms_acceptances user_terms_acceptances_terms_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_terms_acceptances
    ADD CONSTRAINT user_terms_acceptances_terms_version_id_foreign FOREIGN KEY (terms_version_id) REFERENCES public.terms_versions(id) ON DELETE CASCADE;


--
-- Name: user_terms_acceptances user_terms_acceptances_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_terms_acceptances
    ADD CONSTRAINT user_terms_acceptances_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_voucher user_voucher_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_voucher
    ADD CONSTRAINT user_voucher_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: user_voucher user_voucher_voucher_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_voucher
    ADD CONSTRAINT user_voucher_voucher_id_foreign FOREIGN KEY (voucher_id) REFERENCES public.vouchers(id);


--
-- Name: vehicle_arrivals vehicle_arrivals_indent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_arrivals
    ADD CONSTRAINT vehicle_arrivals_indent_id_foreign FOREIGN KEY (indent_id) REFERENCES public.indents(id);


--
-- Name: vehicle_arrivals vehicle_arrivals_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_arrivals
    ADD CONSTRAINT vehicle_arrivals_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id);


--
-- Name: vehicle_arrivals vehicle_arrivals_vehicle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_arrivals
    ADD CONSTRAINT vehicle_arrivals_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES public.vehicles(id);


--
-- Name: vehicle_unload_steps vehicle_unload_steps_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_steps
    ADD CONSTRAINT vehicle_unload_steps_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: vehicle_unload_steps vehicle_unload_steps_vehicle_unload_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_steps
    ADD CONSTRAINT vehicle_unload_steps_vehicle_unload_id_foreign FOREIGN KEY (vehicle_unload_id) REFERENCES public.vehicle_unloads(id) ON DELETE CASCADE;


--
-- Name: vehicle_unload_weighments vehicle_unload_weighments_vehicle_unload_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unload_weighments
    ADD CONSTRAINT vehicle_unload_weighments_vehicle_unload_id_foreign FOREIGN KEY (vehicle_unload_id) REFERENCES public.vehicle_unloads(id) ON DELETE CASCADE;


--
-- Name: vehicle_unloads vehicle_unloads_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: vehicle_unloads vehicle_unloads_siding_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_siding_id_foreign FOREIGN KEY (siding_id) REFERENCES public.sidings(id) ON DELETE CASCADE;


--
-- Name: vehicle_unloads vehicle_unloads_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: vehicle_unloads vehicle_unloads_vehicle_arrival_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_vehicle_arrival_id_foreign FOREIGN KEY (vehicle_arrival_id) REFERENCES public.vehicle_arrivals(id) ON DELETE CASCADE;


--
-- Name: vehicle_unloads vehicle_unloads_vehicle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.vehicle_unloads
    ADD CONSTRAINT vehicle_unloads_vehicle_id_foreign FOREIGN KEY (vehicle_id) REFERENCES public.vehicles(id) ON DELETE CASCADE;


--
-- Name: visibility_demos visibility_demos_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.visibility_demos
    ADD CONSTRAINT visibility_demos_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- Name: wagon_loading wagon_loading_loader_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading
    ADD CONSTRAINT wagon_loading_loader_id_foreign FOREIGN KEY (loader_id) REFERENCES public.loaders(id) ON DELETE SET NULL;


--
-- Name: wagon_loading wagon_loading_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading
    ADD CONSTRAINT wagon_loading_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: wagon_loading wagon_loading_wagon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_loading
    ADD CONSTRAINT wagon_loading_wagon_id_foreign FOREIGN KEY (wagon_id) REFERENCES public.wagons(id) ON DELETE CASCADE;


--
-- Name: wagon_unfit_logs wagon_unfit_logs_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs
    ADD CONSTRAINT wagon_unfit_logs_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: wagon_unfit_logs wagon_unfit_logs_txr_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs
    ADD CONSTRAINT wagon_unfit_logs_txr_id_foreign FOREIGN KEY (txr_id) REFERENCES public.txr(id) ON DELETE CASCADE;


--
-- Name: wagon_unfit_logs wagon_unfit_logs_wagon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagon_unfit_logs
    ADD CONSTRAINT wagon_unfit_logs_wagon_id_foreign FOREIGN KEY (wagon_id) REFERENCES public.wagons(id) ON DELETE CASCADE;


--
-- Name: wagons wagons_rake_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.wagons
    ADD CONSTRAINT wagons_rake_id_foreign FOREIGN KEY (rake_id) REFERENCES public.rakes(id) ON DELETE CASCADE;


--
-- Name: webhook_logs webhook_logs_organization_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_logs
    ADD CONSTRAINT webhook_logs_organization_id_foreign FOREIGN KEY (organization_id) REFERENCES public.organizations(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict yFeWoeuXC8mI8IdmdvarA9Sz46ipjm0Bge3JctwoQRrFo7cb4M4gAO0vEN6N9xI

--
-- PostgreSQL database dump
--

\restrict 4xftb27eqYuVt3Oouw0UTH3GeDqgMzAaBpet8pBM4X1vBUdtCHg0EfNj73OjviI

-- Dumped from database version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1)

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
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2022_08_03_000000_create_vector_extension	1
5	2022_12_14_083707_create_settings_table	1
6	2023_01_16_000001_create_customers_table	1
7	2023_01_16_000002_create_subscriptions_table	1
8	2023_01_16_000003_create_orders_table	1
9	2023_01_16_000004_create_license_keys_table	1
10	2023_01_16_000005_create_license_key_instances_table	1
11	2023_05_28_135232_create_referrals_table	1
12	2024_01_01_000000_create_memories_table	1
13	2026_01_29_041033_create_permission_tables	1
14	2026_01_29_055958_create_notifications_table	1
15	2026_01_29_121401_create_embedding_demos_table	1
16	2026_02_05_125308_create_personal_access_tokens_table	1
17	2026_02_05_161850_create_activity_log_table	1
18	2026_02_05_161851_add_event_column_to_activity_log_table	1
19	2026_02_05_161852_add_batch_uuid_column_to_activity_log_table	1
20	2026_02_06_081136_create_agent_conversations_table	1
21	2026_02_06_152347_create_media_table	1
22	2026_02_06_160415_create_model_flags_table	1
23	2026_02_07_145746_create_features_table	1
24	2026_02_07_145747_create_filament_feature_flags_table	1
25	2026_02_08_042602_app_settings	1
26	2026_02_08_042617_auth_settings	1
27	2026_02_08_042617_seo_settings	1
28	2026_02_08_061704_create_tag_tables	1
29	2026_02_08_070156_create_contact_submissions_table	1
30	2026_02_08_073656_add_onboarding_to_users_table	1
31	2026_02_08_081742_add_userstamps_to_contact_submissions_table	1
32	2026_02_08_120053_create_flags_table	1
33	2026_02_08_120053_create_imports_table	1
34	2026_02_08_120054_create_exports_table	1
35	2026_02_08_120055_create_failed_import_rows_table	1
36	2026_02_08_120419_create_categories_tables	1
37	2026_02_08_125751_create_changelog_entries_table	1
38	2026_02_08_125751_create_posts_table	1
39	2026_02_08_125752_create_help_articles_table	1
40	2026_02_10_154244_create_levels_table	1
41	2026_02_10_154245_create_experiences_table	1
42	2026_02_10_154246_add_level_relationship_to_users_table	1
43	2026_02_10_154247_create_experience_audits_table	1
44	2026_02_10_154248_create_achievements_table	1
45	2026_02_10_154249_create_achievement_user_pivot_table	1
46	2026_02_10_154250_create_streak_activities_table	1
47	2026_02_10_154251_create_streaks_table	1
48	2026_02_10_154252_create_streak_histories_table	1
49	2026_02_10_154253_add_streak_freeze_feature_columns_to_streaks_table	1
50	2026_02_10_154254_remove_level_id_column_from_users_table	1
51	2026_02_13_151120_create_organizations_table	1
52	2026_02_13_151126_add_organization_id_to_permission_tables	1
53	2026_02_13_151126_create_organization_invitations_table	1
54	2026_02_13_151126_create_organization_user_table	1
55	2026_02_13_151127_add_organization_id_to_content_tables	1
56	2026_02_13_151127_backfill_organization_data	1
57	2026_02_13_151811_create_organization_roles_and_assign_members	1
58	2026_02_13_151944_set_global_roles_organization_id_to_zero	1
59	2026_02_13_154344_create_plans_table	1
60	2026_02_13_154345_create_plan_features_table	1
61	2026_02_13_154346_create_plan_subscriptions_table	1
62	2026_02_13_154347_create_plan_subscription_usage_table	1
63	2026_02_13_154348_remove_unique_slug_on_subscriptions_table	1
64	2026_02_13_154349_update_unique_keys_on_features_table	1
65	2026_02_13_154350_remove_cancels_at_from_subscriptions_table	1
66	2026_02_13_154433_create_credits_table	1
67	2026_02_13_154434_create_credit_packs_table	1
68	2026_02_13_154434_create_invoices_table	1
69	2026_02_13_154435_create_payment_gateways_table	1
70	2026_02_13_154439_create_gateway_products_table	1
71	2026_02_13_154439_create_refund_requests_table	1
72	2026_02_13_154440_add_billing_data_to_organizations_table	1
73	2026_02_13_154440_create_billing_metrics_table	1
74	2026_02_13_154440_create_webhook_logs_table	1
75	2026_02_13_154554_add_invoice_payment_gateway_foreign_key	1
76	2026_02_13_165459_create_organization_domains_table	1
77	2026_02_13_165832_add_seat_billing_and_gateway_fields	1
78	2026_02_13_165933_create_billing_settings	1
79	2026_02_14_023808_add_organization_id_to_webhook_logs_table	1
80	2026_02_14_042118_create_terms_versions_table	1
81	2026_02_14_042119_create_user_terms_acceptances_table	1
82	2026_02_14_050359_create_enterprise_inquiries_table	1
83	2026_02_14_053530_create_voucher_scopes_table	1
84	2026_02_14_053532_create_vouchers_table	1
85	2026_02_14_074651_add_timezone_column_to_users_table	1
86	2026_02_14_074841_create_failed_payment_attempts_table	1
87	2026_02_14_074842_create_affiliates_and_related_tables	1
88	2026_02_14_114714_create_pan_analytics_table	1
89	2026_02_14_115657_create_mail_templates_table	1
90	2026_02_14_115658_create_mail_exceptions_table	1
91	2026_02_14_125120_create_shareables_table	1
92	2026_02_14_125501_create_visibility_demos_table	1
93	2026_02_14_130001_add_visibility_columns_to_visibility_demos_table	1
94	2026_02_18_114740_create_sidings_table	1
95	2026_02_18_114757_create_routes_table	1
96	2026_02_18_114757_create_vehicles_table	1
97	2026_02_18_114758_create_freight_rate_master_table	1
98	2026_02_18_114758_create_loaders_table	1
99	2026_02_18_114758_create_user_siding_table	1
100	2026_02_18_114841_create_rakes_table	1
101	2026_02_18_114842_create_coal_stock_table	1
102	2026_02_18_114842_create_indents_table	1
103	2026_02_18_114843_create_vehicle_unload_table	1
104	2026_02_18_114843_create_wagons_table	1
105	2026_02_18_114844_add_indent_fk_to_rakes_table	1
106	2026_02_18_114848_create_vehicle_unload_weighments_table	1
107	2026_02_18_114934_create_txr_table	1
108	2026_02_18_114935_create_guard_inspections_table	1
109	2026_02_18_114935_create_rr_documents_table	1
110	2026_02_18_114936_create_rr_predictions_table	1
111	2026_02_18_115013_create_loader_performance_table	1
112	2026_02_18_115013_create_penalties_table	1
113	2026_02_18_115013_create_siding_performance_table	1
114	2026_02_18_115014_create_sync_queue_table	1
115	2026_02_18_142101_create_siding_user_table	1
116	2026_02_18_142141_create_vehicle_arrivals_table	1
117	2026_02_18_142629_create_stock_ledgers_table	1
118	2026_02_18_143351_add_updated_by_to_rr_documents_table	1
119	2026_02_18_150039_add_userstamps_to_sidings_table	1
120	2026_02_18_150205_add_is_primary_to_siding_user_table	1
121	2026_02_18_155625_add_updated_by_to_txr_table	1
122	2026_02_18_180524_create_power_plants_table	1
123	2026_02_18_180525_create_power_plant_receipts_table	1
124	2026_02_18_183427_create_alerts_table	1
125	2026_02_19_023432_add_shift_to_vehicle_unload_and_arrivals	1
126	2026_02_19_070708_add_calculation_breakdown_to_penalties_table	1
127	2026_02_19_143110_add_rr_enrichment_columns_to_rr_documents_table	1
128	2026_02_21_032752_add_responsibility_and_dispute_fields_to_penalties_table	1
129	2026_02_21_150623_create_penalty_predictions_table	1
130	2026_02_21_150812_create_siding_risk_scores_table	1
131	2026_02_21_151245_add_ai_classification_to_penalties_table	1
132	2026_02_23_102632_add_vehicle_arrival_id_to_vehicle_unload_table	1
133	2026_02_23_112959_create_vehicle_unload_steps_table	1
134	2026_02_24_071628_create_penalty_types_table	1
135	2026_02_24_071641_create_applied_penalties_table	1
136	2026_02_24_101712_create_rake_wagon_loading_table	1
137	2026_02_24_101725_create_rake_weighments_table	1
138	2026_02_24_102807_create_rake_wagon_weighments_table	1
139	2026_02_25_090155_create_daily_vehicle_entries_table	1
140	2026_02_26_144129_make_vehicle_no_nullable_in_daily_vehicle_entries_table	1
141	2026_02_27_093510_create_wagon_unfit_logs_table	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 141, true);


--
-- PostgreSQL database dump complete
--

\unrestrict 4xftb27eqYuVt3Oouw0UTH3GeDqgMzAaBpet8pBM4X1vBUdtCHg0EfNj73OjviI

