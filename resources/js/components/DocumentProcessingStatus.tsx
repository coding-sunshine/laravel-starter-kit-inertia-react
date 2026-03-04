import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';

interface ProcessingDocument {
    id: number;
    file_name: string;
    file_size: string;
    type: 'auto' | 'project' | 'lot';
    queue_status: 'pending' | 'processing' | 'completed' | 'failed';
    status: 'pending_approval' | 'approved' | 'rejected' | 'created';
    processing_started_at?: string;
    processing_completed_at?: string;
    extracted_data?: any;
    admin_notes?: string;
}

interface ProcessingStats {
    total: number;
    pending: number;
    processing: number;
    completed: number;
    failed: number;
}

interface DocumentProcessingStatusProps {
    batchId?: string;
    autoRefresh?: boolean;
    refreshInterval?: number; // in milliseconds
    onDocumentSelect?: (documentIds: number[]) => void;
    showBulkActions?: boolean;
}

const DocumentProcessingStatus: React.FC<DocumentProcessingStatusProps> = ({
    batchId,
    autoRefresh = true,
    refreshInterval = 5000,
    onDocumentSelect,
    showBulkActions = true
}) => {
    const [documents, setDocuments] = useState<ProcessingDocument[]>([]);
    const [stats, setStats] = useState<ProcessingStats>({
        total: 0,
        pending: 0,
        processing: 0,
        completed: 0,
        failed: 0
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedDocuments, setSelectedDocuments] = useState<Set<number>>(new Set());
    const intervalRef = useRef<NodeJS.Timeout>();

    const fetchStatus = async () => {
        try {
            const url = batchId
                ? `/api/v1/documents/batch/${batchId}/status`
                : `/api/v1/documents/queue-status`;

            const response = await axios.get(url);

            if (response.data.success) {
                if (batchId) {
                    setDocuments(response.data.data.documents);
                    setStats(response.data.data.stats);
                } else {
                    setStats(response.data.data.stats);
                    // For queue status, we might not get individual documents
                    // This would need to be handled differently
                }
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to fetch status');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStatus();

        if (autoRefresh) {
            intervalRef.current = setInterval(fetchStatus, refreshInterval);
        }

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [batchId, autoRefresh, refreshInterval]);

    const handleDocumentSelect = (documentId: number, isSelected: boolean) => {
        const newSelected = new Set(selectedDocuments);

        if (isSelected) {
            newSelected.add(documentId);
        } else {
            newSelected.delete(documentId);
        }

        setSelectedDocuments(newSelected);
        onDocumentSelect?.(Array.from(newSelected));
    };

    const handleSelectAll = () => {
        const completedDocuments = documents
            .filter(doc => doc.queue_status === 'completed' && doc.status === 'pending_approval')
            .map(doc => doc.id);

        const newSelected = new Set(completedDocuments);
        setSelectedDocuments(newSelected);
        onDocumentSelect?.(Array.from(newSelected));
    };

    const handleDeselectAll = () => {
        setSelectedDocuments(new Set());
        onDocumentSelect?.([]);
    };

    const getStatusBadge = (queueStatus: string, approvalStatus: string) => {
        if (queueStatus === 'pending') {
            return <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Pending</span>;
        }
        if (queueStatus === 'processing') {
            return <span className="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full animate-pulse">Processing</span>;
        }
        if (queueStatus === 'failed') {
            return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Failed</span>;
        }
        if (queueStatus === 'completed') {
            if (approvalStatus === 'approved') {
                return <span className="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Approved</span>;
            }
            if (approvalStatus === 'rejected') {
                return <span className="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Rejected</span>;
            }
            if (approvalStatus === 'created') {
                return <span className="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Created</span>;
            }
            return <span className="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">Ready for Approval</span>;
        }

        return <span className="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Unknown</span>;
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'project':
                return (
                    <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                    </svg>
                );
            case 'lot':
                return (
                    <svg className="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8zm2 2a1 1 0 000 2h.01a1 1 0 100-2H5zm3 0a1 1 0 000 2h3a1 1 0 100-2H8z" clipRule="evenodd" />
                    </svg>
                );
            default:
                return (
                    <svg className="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clipRule="evenodd" />
                    </svg>
                );
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center p-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span className="ml-2 text-gray-600">Loading status...</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className="p-6 border border-red-200 rounded-lg bg-red-50">
                <div className="flex items-center">
                    <svg className="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                    </svg>
                    <span className="text-red-800 font-medium">Error loading status</span>
                </div>
                <p className="text-red-700 text-sm mt-1">{error}</p>
                <button
                    onClick={() => {
                        setError(null);
                        fetchStatus();
                    }}
                    className="mt-2 text-sm text-red-600 hover:text-red-800 underline"
                >
                    Try again
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Stats Overview */}
            <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div className="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <div className="text-2xl font-bold text-gray-900 dark:text-white">{stats.total}</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Total</div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Pending</div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <div className="text-2xl font-bold text-blue-600">{stats.processing}</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Processing</div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <div className="text-2xl font-bold text-green-600">{stats.completed}</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Completed</div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-lg border p-4">
                    <div className="text-2xl font-bold text-red-600">{stats.failed}</div>
                    <div className="text-sm text-gray-600 dark:text-gray-400">Failed</div>
                </div>
            </div>

            {/* Bulk Actions */}
            {showBulkActions && documents.some(doc => doc.queue_status === 'completed' && doc.status === 'pending_approval') && (
                <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <span className="text-sm font-medium text-blue-900 dark:text-blue-100">
                                {selectedDocuments.size} documents selected
                            </span>
                            <button
                                onClick={handleSelectAll}
                                className="text-sm text-blue-600 hover:text-blue-800 underline"
                            >
                                Select all ready
                            </button>
                            {selectedDocuments.size > 0 && (
                                <button
                                    onClick={handleDeselectAll}
                                    className="text-sm text-blue-600 hover:text-blue-800 underline"
                                >
                                    Deselect all
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Documents List */}
            {documents.length > 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg border overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    {showBulkActions && (
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Select
                                        </th>
                                    )}
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        File
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Progress
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                {documents.map((document) => (
                                    <tr key={document.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        {showBulkActions && (
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {document.queue_status === 'completed' && document.status === 'pending_approval' && (
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedDocuments.has(document.id)}
                                                        onChange={(e) => handleDocumentSelect(document.id, e.target.checked)}
                                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    />
                                                )}
                                            </td>
                                        )}
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {document.file_name}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {document.file_size}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                {getTypeIcon(document.type)}
                                                <span className="ml-2 text-sm text-gray-900 dark:text-white capitalize">
                                                    {document.type}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(document.queue_status, document.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {document.processing_completed_at ? (
                                                <span>Completed</span>
                                            ) : document.processing_started_at ? (
                                                <span>Processing...</span>
                                            ) : (
                                                <span>Queued</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : (
                <div className="text-center py-8 bg-white dark:bg-gray-800 rounded-lg border">
                    <div className="text-gray-500 dark:text-gray-400">
                        No documents found
                    </div>
                </div>
            )}
        </div>
    );
};

export default DocumentProcessingStatus;