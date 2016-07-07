/*
 searches the leaf at the node stored at the page with number
 PageNo
 for the
 key.
 It returns the offset from the postings file, or NONEXISTENT,
 if not found
 */

#include "def.h"

POSTINGSPTR searchLeaf_Right(struct PageHdr *PagePtr, char *key) {
    
    struct KeyRecord *KeyListTraverser;
    int InsertionPosition; /* Position for insertion */
    int FindInsertionPosition_Right(struct KeyRecord * KeyListTraverser, char *Key,
                              int *last, NUMKEYS NumKeys, int Count);
    int Count, last, i;
    last = FALSE;
    Count = 0;
    
    /* Find insertion position */
    KeyListTraverser = PagePtr->KeyListPtr;
    InsertionPosition = FindInsertionPosition_Right(KeyListTraverser, key, &last,
                                              PagePtr->NumKeys, Count);
    
    /* key is the last in the list */
    if (last == TRUE) {
        if (PagePtr->PgNumOfNxtLfPg == NULLPAGENO){
            //printf("*NONE*\n");
            return(NONEXISTENT);
        }else{
            
            PageHdr * p = FetchPage(PagePtr->PgNumOfNxtLfPg);
            printf("%s\n", p->KeyListPtr->StoredKey);
            return (p->KeyListPtr->Posting);
        }
    } else {
        for (i = 0; i < InsertionPosition - 1; i++)
            KeyListTraverser = KeyListTraverser->Next;
        
        printf("%s\n", KeyListTraverser->StoredKey);
        return (KeyListTraverser->Posting);
    }
}
