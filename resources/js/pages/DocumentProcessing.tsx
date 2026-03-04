import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Toaster } from 'react-hot-toast';
import BulkDocumentUpload from '@/components/BulkDocumentUpload';
import DocumentProcessingStatus from '@/components/DocumentProcessingStatus';

interface DocumentProcessingProps {
    batchId?: string;
}

const DocumentProcessing: React.FC<DocumentProcessingProps> = ({ batchId: initialBatchId }) => {
    const [currentBatchId, setCurrentBatchId] = useState<string | undefined>(initialBatchId);
    const [selectedDocuments, setSelectedDocuments] = useState<number[]>([]);
    const [isProcessingBulkAction, setIsProcessingBulkAction] = useState(false);

    const handleUploadComplete = (batchId: string) => {
        setCurrentBatchId(batchId);
    };

    const handleDocumentSelect = (documentIds: number[]) => {
        setSelectedDocuments(documentIds);
    };

    const handleBulkApprove = async () => {
        if (selectedDocuments.length === 0) return;

        setIsProcessingBulkAction(true);
        try {
            const response = await fetch('/api/v1/documents/bulk-approve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
                },
                body: JSON.stringify({
                    document_ids: selectedDocuments
                })
            });

            const result = await response.json();

            if (result.success) {
                setSelectedDocuments([]);
                // The DocumentProcessingStatus component will auto-refresh
            } else {
                throw new Error(result.message || 'Bulk approval failed');
            }
        } catch (error) {
            console.error('Bulk approval error:', error);
        } finally {
            setIsProcessingBulkAction(false);
        }
    };

    const handleBulkReject = async (reason?: string) => {
        if (selectedDocuments.length === 0) return;

        setIsProcessingBulkAction(true);
        try {
            const response = await fetch('/api/v1/documents/bulk-reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
                },
                body: JSON.stringify({
                    document_ids: selectedDocuments,
                    reason
                })
            });

            const result = await response.json();

            if (result.success) {
                setSelectedDocuments([]);
                // The DocumentProcessingStatus component will auto-refresh
            } else {
                throw new Error(result.message || 'Bulk rejection failed');
            }
        } catch (error) {
            console.error('Bulk rejection error:', error);
        } finally {
            setIsProcessingBulkAction(false);
        }
    };

    return (
        <>
            <Head title="Bulk Document Processing" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Bulk Document Processing
                        </h1>
                        <p className="mt-2 text-gray-600 dark:text-gray-400">
                            Upload multiple documents for automated property data extraction and processing
                        </p>
                    </div>

                    {/* Upload Section */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                        <div className="p-6">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                Upload Documents
                            </h2>
                            <BulkDocumentUpload
                                onUploadComplete={handleUploadComplete}
                                maxFiles={20}
                                maxFileSize={10}
                            />
                        </div>
                    </div>

                    {/* Processing Status */}
                    {currentBatchId && (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        Processing Status
                                    </h2>
                                    {selectedDocuments.length > 0 && (
                                        <div className="flex space-x-3">
                                            <button
                                                onClick={handleBulkApprove}
                                                disabled={isProcessingBulkAction}
                                                className={`
                                                    px-4 py-2 rounded-lg text-sm font-medium transition-colors
                                                    ${isProcessingBulkAction
                                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                        : 'bg-green-600 hover:bg-green-700 text-white'
                                                    }
                                                `}
                                            >
                                                {isProcessingBulkAction ? 'Processing...' : `Approve ${selectedDocuments.length}`}
                                            </button>
                                            <button
                                                onClick={() => handleBulkReject()}
                                                disabled={isProcessingBulkAction}
                                                className={`
                                                    px-4 py-2 rounded-lg text-sm font-medium transition-colors
                                                    ${isProcessingBulkAction
                                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                        : 'bg-red-600 hover:bg-red-700 text-white'
                                                    }
                                                `}
                                            >
                                                {isProcessingBulkAction ? 'Processing...' : `Reject ${selectedDocuments.length}`}
                                            </button>
                                        </div>
                                    )}
                                </div>
                                <DocumentProcessingStatus
                                    batchId={currentBatchId}
                                    autoRefresh={true}
                                    refreshInterval={5000}
                                    onDocumentSelect={handleDocumentSelect}
                                    showBulkActions={true}
                                />
                            </div>
                        </div>
                    )}

                    {/* Instructions */}
                    {!currentBatchId && (
                        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 rounded-lg p-6">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-blue-800 dark:text-blue-200">
                                        How to Use Bulk Document Processing
                                    </h3>
                                    <div className="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                        <ul className="list-disc pl-5 space-y-1">
                                            <li><strong>Select Processing Type:</strong> Choose auto-detect for best results, or specify project/lot processing</li>
                                            <li><strong>Upload Files:</strong> Drag and drop up to 20 files (PDF, images, Word docs, text files)</li>
                                            <li><strong>Monitor Progress:</strong> Watch real-time processing status with automatic updates</li>
                                            <li><strong>Review Results:</strong> Use bulk actions to approve or reject extracted data</li>
                                            <li><strong>Background Processing:</strong> Files are processed in the background - you can continue working</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Recent Batches Link */}
                    <div className="text-center mt-8">
                        <a
                            href="/admin/bulk-document-processing"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            View All Batches in Admin Panel
                        </a>
                    </div>
                </div>
            </div>

            <Toaster
                position="top-right"
                toastOptions={{
                    duration: 4000,
                    style: {
                        background: '#363636',
                        color: '#fff',
                    },
                }}
            />
        </>
    );
};

export default DocumentProcessing;