<?php
/**
 *
 */

namespace App\Search;

use App\Conversation;
use App\User;

/**
 * Class Search
 * @package App\Search
 */
class Search
{

    /**
     *
     *
     * @param User $user
     * @param string $q
     * @param array $filters
     * @return mixed
     */
    public function search(User $user, string $q, array $filters) {
        return $this->searchDatabase($user, $q, $filters);
    }

    /**
     *
     *
     * @param User $user
     * @param string $q
     * @param array $filters
     * @return mixed
     */
    private function searchDatabase(User $user, string $q, array $filters) {

        // Get IDs of mailboxes to which user has access
        $mailbox_ids = $user->mailboxesIdsCanView();

        // Like is case insensitive.
        $like = '%'.mb_strtolower($q).'%';

        $query_conversations = Conversation::select('conversations.*')
            // https://github.com/laravel/framework/issues/21242
            // https://github.com/laravel/framework/pull/27675
            ->groupby('conversations.id')
            ->whereIn('conversations.mailbox_id', $mailbox_ids)
            ->join('threads', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        if ($q) {
            $query_conversations->where(function ($query) use ($like, $filters, $q) {
                $query->where('conversations.subject', 'like', $like)
                    ->orWhere('conversations.customer_email', 'like', $like)
                    ->orWhere('conversations.number', (int)$q)
                    ->orWhere('conversations.id', (int)$q)
                    ->orWhere('threads.body', 'like', $like)
                    ->orWhere('threads.from', 'like', $like)
                    ->orWhere('threads.to', 'like', $like)
                    ->orWhere('threads.cc', 'like', $like)
                    ->orWhere('threads.bcc', 'like', $like);

                $query = \Eventy::filter('search.conversations.or_where', $query, $filters, $q);
            });
        }

        // Apply search filters.
        if (!empty($filters['assigned'])) {
            $query_conversations->where('conversations.user_id', $filters['assigned']);
        }
        if (!empty($filters['customer'])) {
            $customer_id = $filters['customer'];
            $query_conversations->where(function ($query) use ($customer_id) {
                $query->where('conversations.customer_id', '=', $customer_id)
                    ->orWhere('threads.created_by_customer_id', '=', $customer_id);
            });
        }
        if (!empty($filters['mailbox'])) {
            $query_conversations->where('conversations.mailbox_id', '=', $filters['mailbox']);
        }
        if (!empty($filters['status'])) {
            if (count($filters['status']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.status', '=', $filters['status'][0]);
            } else {
                $query_conversations->whereIn('conversations.status', $filters['status']);
            }
        }
        if (!empty($filters['state'])) {
            if (count($filters['state']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.state', '=', $filters['state'][0]);
            } else {
                $query_conversations->whereIn('conversations.state', $filters['state']);
            }
        }
        if (!empty($filters['subject'])) {
            $query_conversations->where('conversations.subject', 'like', '%'.mb_strtolower($filters['subject']).'%');
        }
        if (!empty($filters['attachments'])) {
            $has_attachments = ($filters['attachments'] == 'yes' ? true : false);
            $query_conversations->where('conversations.has_attachments', '=', $has_attachments);
        }
        if (!empty($filters['type'])) {
            $query_conversations->where('conversations.has_attachments', '=', $filters['type']);
        }
        if (!empty($filters['body'])) {
            $query_conversations->where('threads.body', 'like', '%'.mb_strtolower($filters['body']).'%');
        }
        if (!empty($filters['number'])) {
            $query_conversations->where('conversations.number', '=', $filters['number']);
        }
        if (!empty($filters['following'])) {
            if ($filters['following'] == 'yes') {
                $query_conversations->join('followers', function ($join) {
                    $join->on('followers.conversation_id', '=', 'conversations.id');
                    $join->where('followers.user_id', auth()->user()->id);
                });
            }
        }
        if (!empty($filters['id'])) {
            $query_conversations->where('conversations.id', '=', $filters['id']);
        }
        if (!empty($filters['after'])) {
            $query_conversations->where('conversations.created_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['after'])));
        }
        if (!empty($filters['before'])) {
            $query_conversations->where('conversations.created_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['before'])));
        }

        $query_conversations = \Eventy::filter('search.conversations.apply_filters', $query_conversations, $filters, $q);

        $query_conversations->orderBy('conversations.last_reply_at', 'DESC');

        return $query_conversations->paginate(Conversation::DEFAULT_LIST_SIZE);
    }
}
